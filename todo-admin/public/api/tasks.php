<?php
/**
 * Tasks API - CRUD operations.
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
startSecureSession();

if (!isLoggedIn()) {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id) {
        $task = getTask($id);
        if (!$task) {
            jsonResponse(['error' => 'Not found'], 404);
        }
        jsonResponse($task);
    }

    $priority = $_GET['priority'] ?? null;
    if ($priority && in_array($priority, ['high', 'medium', 'low'])) {
        jsonResponse(getTasksByPriority($priority));
    }

    $db = getDbConnection();
    $tasks = $db->query(
        'SELECT t.*, c.name AS category_name, c.color AS category_color,
                u.display_name AS assigned_name, u.username AS assigned_username
         FROM tasks t
         LEFT JOIN categories c ON t.category_id = c.id
         LEFT JOIN users u ON t.assigned_to = u.id
         ORDER BY t.sort_order ASC'
    )->fetchAll();
    jsonResponse($tasks);
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    if (empty($input['title'])) {
        jsonResponse(['error' => 'Title is required'], 400);
    }

    $data = [
        'title'       => trim($input['title']),
        'description' => trim($input['description'] ?? ''),
        'priority'    => $input['priority'] ?? 'low',
        'category_id' => $input['category_id'] ?? null,
        'assigned_to' => $input['assigned_to'] ?? null,
        'created_by'  => getCurrentUserId(),
    ];

    if (!in_array($data['priority'], ['high', 'medium', 'low'])) {
        $data['priority'] = 'low';
    }

    $id = createTask($data);
    $task = getTask($id);
    jsonResponse($task, 201);
}

if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        jsonResponse(['error' => 'Invalid JSON'], 400);
    }

    $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
    if (!$id || !getTask($id)) {
        jsonResponse(['error' => 'Task not found'], 404);
    }

    $data = [
        'title'       => trim($input['title'] ?? ''),
        'description' => trim($input['description'] ?? ''),
        'priority'    => $input['priority'] ?? 'low',
        'category_id' => $input['category_id'] ?? null,
        'assigned_to' => $input['assigned_to'] ?? null,
        'status'      => $input['status'] ?? 'pending',
    ];

    updateTask($id, $data);
    jsonResponse(getTask($id));
}

if ($method === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = (int)($input['id'] ?? $_GET['id'] ?? 0);

    if (!$id) {
        jsonResponse(['error' => 'Task ID required'], 400);
    }

    deleteTask($id);
    jsonResponse(['success' => true]);
}

jsonResponse(['error' => 'Method not allowed'], 405);
