<?php
/**
 * First-run setup: create the master admin account.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/captcha.php';
startSecureSession();

if (masterAdminExists()) {
    header('Location: /login.php');
    exit;
}

$error = '';
$captcha = generateCaptcha();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $displayName = trim($_POST['display_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';
    $captchaInput = $_POST['captcha'] ?? '';
    $token = $_POST['csrf_token'] ?? '';

    if (!verifyCsrf($token)) {
        $error = 'Invalid request. Please try again.';
    } elseif (!verifyCaptcha($captchaInput)) {
        $error = 'CAPTCHA answer is incorrect.';
    } elseif (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $passwordConfirm) {
        $error = 'Passwords do not match.';
    } else {
        $db = getDbConnection();
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $db->prepare(
            'INSERT INTO users (username, display_name, password_hash, role, first_login)
             VALUES (?, ?, ?, ?, 0)'
        );
        $stmt->execute([$username, $displayName ?: $username, $hash, 'master_admin']);
        $userId = (int)$db->lastInsertId();

        loginUser([
            'id' => $userId,
            'username' => $username,
            'display_name' => $displayName ?: $username,
            'role' => 'master_admin',
        ]);

        header('Location: /setup-security.php');
        exit;
    }
    $captcha = generateCaptcha();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup - Admin Todo</title>
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
                <h2>Initial Setup</h2>
                <p>Create your master admin account</p>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-error"><?= h($error) ?></div>
            <?php endif; ?>

            <form method="POST" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">

                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required minlength="3"
                           value="<?= h($username ?? '') ?>" autocomplete="username">
                </div>

                <div class="form-group">
                    <label for="display_name">Display Name</label>
                    <input type="text" id="display_name" name="display_name"
                           value="<?= h($displayName ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required minlength="8"
                           autocomplete="new-password">
                </div>

                <div class="form-group">
                    <label for="password_confirm">Confirm Password</label>
                    <input type="password" id="password_confirm" name="password_confirm" required
                           autocomplete="new-password">
                </div>

                <div class="form-group captcha-group">
                    <label for="captcha">CAPTCHA: What is <strong><?= h($captcha['question']) ?></strong></label>
                    <input type="text" id="captcha" name="captcha" required autocomplete="off">
                </div>

                <button type="submit" class="btn btn-primary btn-block">Create Master Admin</button>
            </form>
        </div>
    </div>
</body>
</html>
