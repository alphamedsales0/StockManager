<?php
// employees_list.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/database_connect.php';

try {
    $stmt = $pdo->prepare("
        SELECT id, name, email, role, is_active, created_at
        FROM users
        WHERE role = 'employee'
        ORDER BY name ASC
    ");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'users' => $users]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}