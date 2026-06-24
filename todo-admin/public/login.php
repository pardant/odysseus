<?php
/**
 * Login page with CAPTCHA and optional TOTP.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/captcha.php';
require_once __DIR__ . '/../includes/totp.php';
startSecureSession();

if (isLoggedIn()) {
    header('Location: /dashboard.php');
    exit;
}

if (!masterAdminExists()) {
    header('Location: /setup.php');
    exit;
}

$error = '';
$showTotp = false;
$captcha = generateCaptcha();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $captchaInput = $_POST['captcha'] ?? '';
    $totpCode = $_POST['totp_code'] ?? '';
    $token = $_POST['csrf_token'] ?? '';
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    if (!verifyCsrf($token)) {
        $error = 'Invalid request. Please try again.';
    } elseif (isRateLimited($ip)) {
        $error = 'Too many login attempts. Please wait 15 minutes.';
    } elseif (!verifyCaptcha($captchaInput)) {
        $error = 'CAPTCHA answer is incorrect.';
    } else {
        $db = getDbConnection();
        $stmt = $db->prepare('SELECT * FROM users WHERE username = ? AND is_active = 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            if ($user['totp_enabled'] && $user['totp_secret']) {
                if (empty($totpCode)) {
                    $showTotp = true;
                    $error = '';
                    $_SESSION['pending_user_id'] = $user['id'];
                } elseif (!TOTP::verify($user['totp_secret'], $totpCode)) {
                    $error = 'Invalid authentication code.';
                    recordLoginAttempt($ip, $username);
                } else {
                    loginUser($user);
                    if ($user['first_login']) {
                        header('Location: /setup-security.php');
                    } else {
                        header('Location: /dashboard.php');
                    }
                    exit;
                }
            } else {
                loginUser($user);
                if ($user['first_login']) {
                    header('Location: /setup-security.php');
                } else {
                    header('Location: /dashboard.php');
                }
                exit;
            }
        } else {
            recordLoginAttempt($ip, $username);
            $error = 'Invalid username or password.';
        }
    }
    $captcha = generateCaptcha();
}

// Handle pending TOTP verification
if (isset($_SESSION['pending_user_id']) && $_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['totp_code'])) {
    $db = getDbConnection();
    $stmt = $db->prepare('SELECT * FROM users WHERE id = ? AND is_active = 1');
    $stmt->execute([$_SESSION['pending_user_id']]);
    $user = $stmt->fetch();

    if ($user && TOTP::verify($user['totp_secret'], $_POST['totp_code'])) {
        unset($_SESSION['pending_user_id']);
        loginUser($user);
        if ($user['first_login']) {
            header('Location: /setup-security.php');
        } else {
            header('Location: /dashboard.php');
        }
        exit;
    } else {
        $error = 'Invalid authentication code.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - Admin Todo</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="logo-icon small">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                    </svg>
                </div>
                <h2>Sign In</h2>
                <p>Enter your credentials to continue</p>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-error"><?= h($error) ?></div>
            <?php endif; ?>

            <?php if ($showTotp): ?>
            <form method="POST" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                <input type="hidden" name="username" value="<?= h($username) ?>">
                <input type="hidden" name="password" value="<?= h($password) ?>">
                <input type="hidden" name="captcha" value="<?= h((string)($_SESSION['captcha_answer'] ?? '')) ?>">

                <div class="form-group">
                    <label for="totp_code">Authentication Code</label>
                    <input type="text" id="totp_code" name="totp_code" required
                           pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code"
                           placeholder="6-digit code">
                    <small>Enter the code from your authenticator app</small>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Verify</button>
            </form>
            <?php else: ?>
            <form method="POST" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">

                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required
                           value="<?= h($username ?? '') ?>" autocomplete="username">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required
                           autocomplete="current-password">
                </div>

                <div class="form-group captcha-group">
                    <label for="captcha">CAPTCHA: What is <strong><?= h($captcha['question']) ?></strong></label>
                    <input type="text" id="captcha" name="captcha" required autocomplete="off">
                </div>

                <button type="submit" class="btn btn-primary btn-block">Sign In</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
