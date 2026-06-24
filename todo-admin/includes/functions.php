<?php
/**
 * Shared utility functions.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';

function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function getAllUsers(): array {
    $db = getDbConnection();
    return $db->query('SELECT id, username, display_name, role, is_active FROM users ORDER BY username')->fetchAll();
}

function getActiveUsers(): array {
    $db = getDbConnection();
    return $db->query('SELECT id, username, display_name, role FROM users WHERE is_active = 1 ORDER BY username')->fetchAll();
}

function getAllCategories(): array {
    $db = getDbConnection();
    return $db->query('SELECT * FROM categories ORDER BY name')->fetchAll();
}

function getTasksByPriority(string $priority): array {
    $db = getDbConnection();
    $stmt = $db->prepare(
        'SELECT t.*, c.name AS category_name, c.color AS category_color,
                u.display_name AS assigned_name, u.username AS assigned_username
         FROM tasks t
         LEFT JOIN categories c ON t.category_id = c.id
         LEFT JOIN users u ON t.assigned_to = u.id
         WHERE t.priority = ?
         ORDER BY t.sort_order ASC, t.created_at DESC'
    );
    $stmt->execute([$priority]);
    return $stmt->fetchAll();
}

function getTask(int $id): ?array {
    $db = getDbConnection();
    $stmt = $db->prepare('SELECT * FROM tasks WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function createTask(array $data): int {
    $db = getDbConnection();
    $stmt = $db->prepare(
        'INSERT INTO tasks (title, description, priority, category_id, assigned_to, created_by, sort_order)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $maxOrder = $db->query("SELECT COALESCE(MAX(sort_order), 0) FROM tasks WHERE priority = '{$data['priority']}'")->fetchColumn();
    $stmt->execute([
        $data['title'],
        $data['description'] ?? null,
        $data['priority'] ?? 'low',
        $data['category_id'] ?: null,
        $data['assigned_to'] ?: null,
        $data['created_by'],
        $maxOrder + 1,
    ]);
    return (int)$db->lastInsertId();
}

function updateTask(int $id, array $data): bool {
    $db = getDbConnection();
    $stmt = $db->prepare(
        'UPDATE tasks SET title = ?, description = ?, priority = ?, category_id = ?, assigned_to = ?, status = ?
         WHERE id = ?'
    );
    return $stmt->execute([
        $data['title'],
        $data['description'] ?? null,
        $data['priority'] ?? 'low',
        $data['category_id'] ?: null,
        $data['assigned_to'] ?: null,
        $data['status'] ?? 'pending',
        $id,
    ]);
}

function deleteTask(int $id): bool {
    $db = getDbConnection();
    $stmt = $db->prepare('DELETE FROM tasks WHERE id = ?');
    return $stmt->execute([$id]);
}

function reorderTasks(array $order, string $priority): void {
    $db = getDbConnection();
    $stmt = $db->prepare('UPDATE tasks SET sort_order = ?, priority = ? WHERE id = ?');
    foreach ($order as $position => $taskId) {
        $stmt->execute([$position, $priority, (int)$taskId]);
    }
}

function createCategory(string $name, string $color, ?int $createdBy): int {
    $db = getDbConnection();
    $stmt = $db->prepare('INSERT INTO categories (name, color, created_by) VALUES (?, ?, ?)');
    $stmt->execute([$name, $color, $createdBy]);
    return (int)$db->lastInsertId();
}

function deleteCategory(int $id): bool {
    $db = getDbConnection();
    $stmt = $db->prepare('DELETE FROM categories WHERE id = ?');
    return $stmt->execute([$id]);
}
