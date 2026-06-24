<?php
/**
 * Post-registration security setup: security question + TOTP auth.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/totp.php';
startSecureSession();
requireLogin();

$user = getCurrentUser();
if (!$user) {
    header('Location: /login.php');
    exit;
}

$error = '';
$success = '';
$step = $_GET['step'] ?? '1';

$totpSecret = $_SESSION['setup_totp_secret'] ?? TOTP::generateSecret();
$_SESSION['setup_totp_secret'] = $totpSecret;
$totpUri = TOTP::getProvisioningUri($totpSecret, $user['username']);

$securityQuestions = [
    'What is your favourite dog name?',
    'What was the name of your first school?',
    'What is your mother\'s maiden name?',
    'What city were you born in?',
    'What was the name of your childhood best friend?',
    'What is your favourite movie?',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrf($token)) {
        $error = 'Invalid request.';
    } else {
        $db = getDbConnection();

        if ($step === '1') {
            $question = $_POST['security_question'] ?? '';
            $customQuestion = trim($_POST['custom_question'] ?? '');
            $answer = trim($_POST['security_answer'] ?? '');

            if ($question === 'custom' && !empty($customQuestion)) {
                $question = $customQuestion;
            }

            if (empty($question) || empty($answer)) {
                $error = 'Please fill in all fields.';
            } else {
                $answerHash = password_hash(strtolower($answer), PASSWORD_BCRYPT, ['cost' => 12]);
                $stmt = $db->prepare('UPDATE users SET security_question = ?, security_answer_hash = ? WHERE id = ?');
                $stmt->execute([$question, $answerHash, $user['id']]);

                header('Location: /setup-security.php?step=2');
                exit;
            }
        } elseif ($step === '2') {
            $totpCode = $_POST['totp_code'] ?? '';
            $enableTotp = isset($_POST['enable_totp']);

            if ($enableTotp) {
                if (empty($totpCode) || !TOTP::verify($totpSecret, $totpCode)) {
                    $error = 'Invalid code. Please try again.';
                } else {
                    $stmt = $db->prepare('UPDATE users SET totp_secret = ?, totp_enabled = 1, first_login = 0 WHERE id = ?');
                    $stmt->execute([$totpSecret, $user['id']]);
                    unset($_SESSION['setup_totp_secret']);

                    header('Location: /dashboard.php');
                    exit;
                }
            } else {
                $stmt = $db->prepare('UPDATE users SET first_login = 0 WHERE id = ?');
                $stmt->execute([$user['id']]);
                unset($_SESSION['setup_totp_secret']);

                header('Location: /dashboard.php');
                exit;
            }
        }
    }
}

// If user needs to change password (first login with generated password)
$needsPasswordChange = isset($_GET['change_password']) || ($user['first_login'] && $user['role'] !== 'master_admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Setup - Admin Todo</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card auth-card-wide">
            <div class="auth-header">
                <div class="logo-icon small">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    </svg>
                </div>
                <h2>Security Setup</h2>
                <p>Step <?= h($step) ?> of 2</p>
            </div>

            <div class="setup-progress">
                <div class="progress-step <?= $step >= '1' ? 'active' : '' ?>">
                    <span class="step-num">1</span>
                    <span class="step-label">Security Question</span>
                </div>
                <div class="progress-line <?= $step >= '2' ? 'active' : '' ?>"></div>
                <div class="progress-step <?= $step >= '2' ? 'active' : '' ?>">
                    <span class="step-num">2</span>
                    <span class="step-label">Authentication</span>
                </div>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-error"><?= h($error) ?></div>
            <?php endif; ?>

            <?php if ($step === '1'): ?>
            <?php if ($needsPasswordChange): ?>
            <form method="POST" class="auth-form" id="password-form" action="/api/change-password.php">
                <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                <h3>Change Your Password</h3>
                <p class="text-muted">You must set a new password before continuing.</p>

                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" required minlength="8">
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
                </div>
                <button type="submit" class="btn btn-primary btn-block">Update Password</button>
                <hr style="margin: 1.5rem 0; border-color: var(--border);">
            </form>
            <?php endif; ?>

            <form method="POST" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                <h3>Security Question</h3>
                <p class="text-muted">This will be used for account recovery.</p>

                <div class="form-group">
                    <label for="security_question">Select a question</label>
                    <select id="security_question" name="security_question" class="form-select">
                        <?php foreach ($securityQuestions as $q): ?>
                        <option value="<?= h($q) ?>"><?= h($q) ?></option>
                        <?php endforeach; ?>
                        <option value="custom">Write your own question...</option>
                    </select>
                </div>

                <div class="form-group" id="custom-question-group" style="display: none;">
                    <label for="custom_question">Your question</label>
                    <input type="text" id="custom_question" name="custom_question">
                </div>

                <div class="form-group">
                    <label for="security_answer">Your Answer</label>
                    <input type="text" id="security_answer" name="security_answer" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Continue</button>
            </form>

            <?php elseif ($step === '2'): ?>
            <form method="POST" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                <h3>Two-Factor Authentication</h3>
                <p class="text-muted">Scan this QR code with your authenticator app (Google Authenticator, Authy, etc.)</p>

                <div class="totp-setup">
                    <div class="qr-placeholder" id="qr-code"></div>
                    <div class="totp-secret-display">
                        <small>Manual entry key:</small>
                        <code><?= h($totpSecret) ?></code>
                    </div>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="enable_totp" id="enable_totp" checked>
                        Enable two-factor authentication
                    </label>
                </div>

                <div class="form-group" id="totp-verify-group">
                    <label for="totp_code">Enter the 6-digit code from your app</label>
                    <input type="text" id="totp_code" name="totp_code" pattern="[0-9]{6}" maxlength="6"
                           autocomplete="one-time-code" placeholder="000000">
                </div>

                <button type="submit" class="btn btn-primary btn-block">Complete Setup</button>
                <button type="submit" name="skip" class="btn btn-ghost btn-block" style="margin-top: 0.5rem;"
                        onclick="document.getElementById('enable_totp').checked = false;">Skip for now</button>
            </form>

            <script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>
            <script>
                const uri = <?= json_encode($totpUri) ?>;
                const qr = qrcode(0, 'M');
                qr.addData(uri);
                qr.make();
                document.getElementById('qr-code').innerHTML = qr.createSvgTag(4);

                document.getElementById('enable_totp').addEventListener('change', function() {
                    document.getElementById('totp-verify-group').style.display = this.checked ? '' : 'none';
                    document.getElementById('totp_code').required = this.checked;
                });

                document.getElementById('security_question')?.addEventListener('change', function() {
                    document.getElementById('custom-question-group').style.display =
                        this.value === 'custom' ? '' : 'none';
                });
            </script>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.getElementById('security_question')?.addEventListener('change', function() {
            document.getElementById('custom-question-group').style.display =
                this.value === 'custom' ? '' : 'none';
        });
    </script>
</body>
</html>
