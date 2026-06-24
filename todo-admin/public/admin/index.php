<?php
/**
 * Master Admin Panel - user management and system overview.
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
$categories = getAllCategories();

$db = getDbConnection();
$taskStats = $db->query("SELECT priority, COUNT(*) as cnt FROM tasks GROUP BY priority")->fetchAll(PDO::FETCH_KEY_PAIR);
$totalTasks = array_sum($taskStats);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Admin Todo</title>
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
            <a href="/admin/" class="nav-item active">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                <span>Admin Panel</span>
            </a>
            <a href="/admin/users.php" class="nav-item">
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
                <h1>Admin Panel</h1>
            </div>
        </header>

        <div class="admin-grid">
            <div class="stat-card">
                <div class="stat-card-icon" style="background: var(--primary-light); color: var(--primary);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                </div>
                <div class="stat-card-info">
                    <span class="stat-card-value"><?= count($allUsers) ?></span>
                    <span class="stat-card-label">Total Users</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-card-icon" style="background: #fef3c7; color: #f59e0b;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                </div>
                <div class="stat-card-info">
                    <span class="stat-card-value"><?= $totalTasks ?></span>
                    <span class="stat-card-label">Total Tasks</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-card-icon" style="background: #fee2e2; color: #ef4444;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                </div>
                <div class="stat-card-info">
                    <span class="stat-card-value"><?= $taskStats['high'] ?? 0 ?></span>
                    <span class="stat-card-label">High Priority</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-card-icon" style="background: #d1fae5; color: #10b981;">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                </div>
                <div class="stat-card-info">
                    <span class="stat-card-value"><?= count($categories) ?></span>
                    <span class="stat-card-label">Categories</span>
                </div>
            </div>
        </div>

        <div class="admin-section">
            <div class="section-header">
                <h2>Quick Actions</h2>
            </div>
            <div class="quick-actions">
                <a href="/admin/users.php" class="action-card">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
                    <span>Add New User</span>
                    <p>Create accounts with generated passwords</p>
                </a>
                <a href="/dashboard.php" class="action-card">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                    <span>Task Board</span>
                    <p>Manage tasks and priorities</p>
                </a>
            </div>
        </div>

        <div class="admin-section">
            <div class="section-header">
                <h2>Recent Users</h2>
                <a href="/admin/users.php" class="btn btn-outline btn-sm">View All</a>
            </div>
            <div class="data-table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Display Name</th>
                            <th>Role</th>
                            <th>2FA</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($allUsers, 0, 5) as $u): ?>
                        <tr>
                            <td><strong><?= h($u['username']) ?></strong></td>
                            <td><?= h($u['display_name'] ?: '-') ?></td>
                            <td><span class="badge badge-<?= $u['role'] ?>"><?= h(ucfirst(str_replace('_', ' ', $u['role']))) ?></span></td>
                            <td>-</td>
                            <td>
                                <span class="status-dot <?= $u['is_active'] ? 'active' : 'inactive' ?>"></span>
                                <?= $u['is_active'] ? 'Active' : 'Inactive' ?>
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
