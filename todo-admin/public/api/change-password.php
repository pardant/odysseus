<?php
/**
 * Password change API - used during first login flow.
 */
require_once __DIR__ . '/../../includes/auth.php';
startSecureSession();

if (!isLoggedIn()) {
    header('Location: /login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /setup-security.php');
    exit;
}

$token = $_POST['csrf_token'] ?? '';
if (!verifyCsrf($token)) {
    header('Location: /setup-security.php?error=invalid_request');
    exit;
}

$newPassword = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if (strlen($newPassword) < 8) {
    header('Location: /setup-security.php?change_password=1&error=short_password');
    exit;
}

if ($newPassword !== $confirmPassword) {
    header('Location: /setup-security.php?change_password=1&error=mismatch');
    exit;
}

$db = getDbConnection();
$hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
$stmt = $db->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
$stmt->execute([$hash, getCurrentUserId()]);

header('Location: /setup-security.php');
exit;
