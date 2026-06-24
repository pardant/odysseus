<?php
/**
 * User management page - create users with generated passwords.
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
startSecureSession();
requireLogin();

$user = getCurrentUser();
if (!in_array($user['role'], ['admin', 'master_admin'])) {
    http_response_code(403);
    echo 'Access denied.';
    exit;
}

$allUsers = getAllUsers();
$error = '';
$success = '';
$generatedPassword = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $token = $_POST['csrf_token'] ?? '';

    if (!verifyCsrf($token)) {
        $error = 'Invalid request.';
    } elseif ($action === 'create') {
        $newUsername = trim($_POST['username'] ?? '');
        $displayName = trim($_POST['display_name'] ?? '');
        $role = $_POST['role'] ?? 'user';

        if (strlen($newUsername) < 3) {
            $error = 'Username must be at least 3 characters.';
        } elseif (!in_array($role, ['user', 'admin'])) {
            $error = 'Invalid role.';
        } else {
            $db = getDbConnection();
            $check = $db->prepare('SELECT id FROM users WHERE username = ?');
            $check->execute([$newUsername]);
            if ($check->fetch()) {
                $error = 'Username already exists.';
            } else {
                $generatedPassword = bin2hex(random_bytes(6));
                $hash = password_hash($generatedPassword, PASSWORD_BCRYPT, ['cost' => 12]);

                $stmt = $db->prepare(
                    'INSERT INTO users (username, display_name, password_hash, role, first_login)
                     VALUES (?, ?, ?, ?, 1)'
                );
                $stmt->execute([$newUsername, $displayName ?: $newUsername, $hash, $role]);
                $success = "User created. Generated password: ";
                $allUsers = getAllUsers();
            }
        }
    } elseif ($action === 'toggle') {
        $toggleId = (int)($_POST['user_id'] ?? 0);
        if ($toggleId && $toggleId !== $user['id']) {
            $db = getDbConnection();
            $stmt = $db->prepare('UPDATE users SET is_active = NOT is_active WHERE id = ? AND role != ?');
            $stmt->execute([$toggleId, 'master_admin']);
            $success = 'User status updated.';
            $allUsers = getAllUsers();
        }
    } elseif ($action === 'reset_password') {
        $resetId = (int)($_POST['user_id'] ?? 0);
        if ($resetId) {
            $generatedPassword = bin2hex(random_bytes(6));
            $hash = password_hash($generatedPassword, PASSWORD_BCRYPT, ['cost' => 12]);
            $db = getDbConnection();
            $stmt = $db->prepare('UPDATE users SET password_hash = ?, first_login = 1 WHERE id = ?');
            $stmt->execute([$hash, $resetId]);
            $success = "Password reset. New password: ";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Admin Todo</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="app-page">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <div class="logo-icon tiny">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                    </svg>
                </div>
                <span>Admin Todo</span>
            </div>
        </div>

        <nav class="sidebar-nav">
            <a href="/dashboard.php" class="nav-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                <span>Dashboard</span>
            </a>
            <a href="/admin/" class="nav-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                <span>Admin Panel</span>
            </a>
            <a href="/admin/users.php" class="nav-item active">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                <span>User Management</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar"><?= strtoupper(substr($user['display_name'] ?: $user['username'], 0, 1)) ?></div>
                <div class="user-details">
                    <span class="user-name"><?= h($user['display_name'] ?: $user['username']) ?></span>
                    <span class="user-role"><?= h(ucfirst(str_replace('_', ' ', $user['role']))) ?></span>
                </div>
            </div>
            <a href="/logout.php" class="nav-item logout-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                <span>Sign Out</span>
            </a>
        </div>
    </aside>

    <main class="main-content">
        <header class="top-bar">
            <div class="top-bar-left">
                <button class="mobile-menu-btn" id="mobile-menu-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h18M3 6h18M3 18h18"/></svg>
                </button>
                <h1>User Management</h1>
            </div>
        </header>

        <?php if ($error): ?>
        <div class="alert alert-error"><?= h($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
        <div class="alert alert-success">
            <?= h($success) ?>
            <?php if ($generatedPassword): ?>
            <code class="generated-password"><?= h($generatedPassword) ?></code>
            <small>(Save this - it won't be shown again)</small>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="admin-section">
            <div class="section-header">
                <h2>Create New User</h2>
            </div>
            <form method="POST" class="create-user-form">
                <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                <input type="hidden" name="action" value="create">

                <div class="form-row">
                    <div class="form-group">
                        <label for="new-username">Username</label>
                        <input type="text" id="new-username" name="username" required minlength="3">
                    </div>
                    <div class="form-group">
                        <label for="new-display">Display Name</label>
                        <input type="text" id="new-display" name="display_name">
                    </div>
                    <div class="form-group">
                        <label for="new-role">Role</label>
                        <select id="new-role" name="role" class="form-select">
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="form-group form-group-btn">
                        <button type="submit" class="btn btn-primary">Create User</button>
                    </div>
                </div>
                <small class="text-muted">A random password will be generated. The user must set up security on first login.</small>
            </form>
        </div>

        <div class="admin-section">
            <div class="section-header">
                <h2>All Users</h2>
            </div>
            <div class="data-table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Display Name</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allUsers as $u): ?>
                        <tr>
                            <td><?= $u['id'] ?></td>
                            <td><strong><?= h($u['username']) ?></strong></td>
                            <td><?= h($u['display_name'] ?: '-') ?></td>
                            <td><span class="badge badge-<?= $u['role'] ?>"><?= h(ucfirst(str_replace('_', ' ', $u['role']))) ?></span></td>
                            <td>
                                <span class="status-dot <?= $u['is_active'] ? 'active' : 'inactive' ?>"></span>
                                <?= $u['is_active'] ? 'Active' : 'Inactive' ?>
                            </td>
                            <td><?= date('M j, Y', strtotime($u['created_at'] ?? 'now')) ?></td>
                            <td class="actions-cell">
                                <?php if ($u['role'] !== 'master_admin'): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline" title="Toggle active status">
                                        <?= $u['is_active'] ? 'Disable' : 'Enable' ?>
                                    </button>
                                </form>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Reset password for this user?');">
                                    <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
                                    <input type="hidden" name="action" value="reset_password">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-ghost">Reset PW</button>
                                </form>
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script src="/assets/js/app.js"></script>
</body>
</html>
