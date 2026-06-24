<?php
/**
 * Main dashboard - task management with drag-and-drop priority columns.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();
requireLogin();

$user = getCurrentUser();
if (!$user) {
    header('Location: /login.php');
    exit;
}

if ($user['first_login']) {
    header('Location: /setup-security.php');
    exit;
}

$highTasks = getTasksByPriority('high');
$mediumTasks = getTasksByPriority('medium');
$lowTasks = getTasksByPriority('low');
$categories = getAllCategories();
$users = getActiveUsers();

$totalTasks = count($highTasks) + count($mediumTasks) + count($lowTasks);
$isAdmin = in_array($user['role'], ['admin', 'master_admin']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Admin Todo</title>
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
            <button class="sidebar-toggle" id="sidebar-toggle" aria-label="Toggle sidebar">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h18M3 6h18M3 18h18"/></svg>
            </button>
        </div>

        <nav class="sidebar-nav">
            <a href="/dashboard.php" class="nav-item active">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                <span>Dashboard</span>
            </a>
            <?php if ($isAdmin): ?>
            <a href="/admin/" class="nav-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                <span>Admin Panel</span>
            </a>
            <?php endif; ?>
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
                <h1>Task Board</h1>
            </div>
            <div class="top-bar-right">
                <div class="stat-badge">
                    <span class="stat-number"><?= $totalTasks ?></span>
                    <span class="stat-label">Total Tasks</span>
                </div>
                <button class="btn btn-primary" id="new-task-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    New Task
                </button>
                <button class="btn btn-outline" id="manage-categories-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                    Categories
                </button>
            </div>
        </header>

        <div class="board">
            <div class="board-column" data-priority="high">
                <div class="column-header high">
                    <div class="column-title">
                        <span class="priority-dot high"></span>
                        <h3>High Priority</h3>
                        <span class="task-count" id="count-high"><?= count($highTasks) ?></span>
                    </div>
                </div>
                <div class="task-list" id="list-high" data-priority="high">
                    <?php foreach ($highTasks as $task): ?>
                    <?php include __DIR__ . '/partials/task-card.php'; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="board-column" data-priority="medium">
                <div class="column-header medium">
                    <div class="column-title">
                        <span class="priority-dot medium"></span>
                        <h3>Medium Priority</h3>
                        <span class="task-count" id="count-medium"><?= count($mediumTasks) ?></span>
                    </div>
                </div>
                <div class="task-list" id="list-medium" data-priority="medium">
                    <?php foreach ($mediumTasks as $task): ?>
                    <?php include __DIR__ . '/partials/task-card.php'; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="board-column" data-priority="low">
                <div class="column-header low">
                    <div class="column-title">
                        <span class="priority-dot low"></span>
                        <h3>Low Priority</h3>
                        <span class="task-count" id="count-low"><?= count($lowTasks) ?></span>
                    </div>
                </div>
                <div class="task-list" id="list-low" data-priority="low">
                    <?php foreach ($lowTasks as $task): ?>
                    <?php include __DIR__ . '/partials/task-card.php'; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Task Modal -->
    <div class="modal-overlay" id="task-modal">
        <div class="modal">
            <div class="modal-header">
                <h3 id="modal-title">New Task</h3>
                <button class="modal-close" id="modal-close">&times;</button>
            </div>
            <form id="task-form" class="modal-body">
                <input type="hidden" id="task-id" name="id" value="">

                <div class="form-group">
                    <label for="task-title">Task Name</label>
                    <input type="text" id="task-title" name="title" required maxlength="255">
                </div>

                <div class="form-group">
                    <label for="task-desc">Description</label>
                    <textarea id="task-desc" name="description" rows="3"></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="task-priority">Priority</label>
                        <select id="task-priority" name="priority" class="form-select">
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="task-status">Status</label>
                        <select id="task-status" name="status" class="form-select">
                            <option value="pending">Pending</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="task-category">Category</label>
                        <select id="task-category" name="category_id" class="form-select">
                            <option value="">None</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" data-color="<?= h($cat['color']) ?>"><?= h($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="task-assigned">Assign To</label>
                        <select id="task-assigned" name="assigned_to" class="form-select">
                            <option value="">Unassigned</option>
                            <?php foreach ($users as $u): ?>
                            <option value="<?= $u['id'] ?>"><?= h($u['display_name'] ?: $u['username']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost" id="delete-task-btn" style="display:none;">Delete</button>
                    <div class="modal-actions">
                        <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Task</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Categories Modal -->
    <div class="modal-overlay" id="categories-modal">
        <div class="modal">
            <div class="modal-header">
                <h3>Manage Categories</h3>
                <button class="modal-close" onclick="closeCategoriesModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="category-list" id="category-list">
                    <?php foreach ($categories as $cat): ?>
                    <div class="category-item" data-id="<?= $cat['id'] ?>">
                        <span class="category-color" style="background: <?= h($cat['color']) ?>"></span>
                        <span class="category-name"><?= h($cat['name']) ?></span>
                        <button class="btn-icon delete-category" data-id="<?= $cat['id'] ?>" title="Delete category">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>

                <form id="add-category-form" class="add-category-form">
                    <input type="text" name="name" placeholder="Category name" required maxlength="100">
                    <input type="color" name="color" value="#6366f1">
                    <button type="submit" class="btn btn-primary btn-sm">Add</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        const CSRF_TOKEN = <?= json_encode(csrfToken()) ?>;
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script src="/assets/js/app.js"></script>
</body>
</html>
