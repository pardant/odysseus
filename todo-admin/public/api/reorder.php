<?php
/**
 * Task reorder API - handles drag-and-drop position changes.
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
startSecureSession();

if (!isLoggedIn()) {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    jsonResponse(['error' => 'Invalid JSON'], 400);
}

$priority = $input['priority'] ?? '';
$order = $input['order'] ?? [];

if (!in_array($priority, ['high', 'medium', 'low'])) {
    jsonResponse(['error' => 'Invalid priority'], 400);
}

if (!is_array($order)) {
    jsonResponse(['error' => 'Order must be an array of task IDs'], 400);
}

reorderTasks($order, $priority);
jsonResponse(['success' => true]);
