<?php
/**
 * Categories API - CRUD operations.
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
startSecureSession();

if (!isLoggedIn()) {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    jsonResponse(getAllCategories());
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    $name = trim($input['name'] ?? '');
    $color = $input['color'] ?? '#6366f1';

    if (empty($name)) {
        jsonResponse(['error' => 'Name is required'], 400);
    }

    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
        $color = '#6366f1';
    }

    $id = createCategory($name, $color, getCurrentUserId());
    jsonResponse(['id' => $id, 'name' => $name, 'color' => $color], 201);
}

if ($method === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = (int)($input['id'] ?? $_GET['id'] ?? 0);

    if (!$id) {
        jsonResponse(['error' => 'Category ID required'], 400);
    }

    deleteCategory($id);
    jsonResponse(['success' => true]);
}

jsonResponse(['error' => 'Method not allowed'], 405);
