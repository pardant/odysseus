<?php
/**
 * Authentication helpers.
 */

require_once __DIR__ . '/../config/database.php';

function h(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function startSecureSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start([
            'cookie_httponly' => true,
            'cookie_samesite' => 'Strict',
            'use_strict_mode' => true,
        ]);
    }
}

function isLoggedIn(): bool {
    startSecureSession();
    return isset($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
}

function requireMasterAdmin(): void {
    requireLogin();
    if (getCurrentUserRole() !== 'master_admin') {
        http_response_code(403);
        echo 'Access denied.';
        exit;
    }
}

function getCurrentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }
    $db = getDbConnection();
    $stmt = $db->prepare('SELECT * FROM users WHERE id = ? AND is_active = 1');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

function getCurrentUserId(): ?int {
    return $_SESSION['user_id'] ?? null;
}

function getCurrentUserRole(): ?string {
    return $_SESSION['user_role'] ?? null;
}

function loginUser(array $user): void {
    startSecureSession();
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['display_name'] = $user['display_name'] ?: $user['username'];
}

function logoutUser(): void {
    startSecureSession();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function masterAdminExists(): bool {
    $db = getDbConnection();
    $stmt = $db->query("SELECT COUNT(*) FROM users WHERE role = 'master_admin'");
    return (int)$stmt->fetchColumn() > 0;
}

function recordLoginAttempt(string $ip, ?string $username = null): void {
    $db = getDbConnection();
    $stmt = $db->prepare('INSERT INTO login_attempts (ip_address, username) VALUES (?, ?)');
    $stmt->execute([$ip, $username]);
}

function isRateLimited(string $ip): bool {
    $db = getDbConnection();
    $stmt = $db->prepare(
        'SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)'
    );
    $stmt->execute([$ip]);
    return (int)$stmt->fetchColumn() >= 10;
}

function csrfToken(): string {
    startSecureSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(string $token): bool {
    return hash_equals(csrfToken(), $token);
}
