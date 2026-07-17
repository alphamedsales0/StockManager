<?php
// employees_create.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/database_connect.php';

$input = json_decode(file_get_contents('php://input'), true);
$name = $input['name'] ?? '';
$email = $input['email'] ?? '';
$password = $input['password'] ?? '';
$role = 'employee'; // Standard

if (!$name || !$email || !$password) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Name, E-Mail und Passwort sind erforderlich']);
    exit;
}

$hashed = password_hash($password, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare("
        INSERT INTO users (name, email, password_hash, role, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$name, $email, $hashed, $role]);
    $id = $pdo->lastInsertId();

    echo json_encode(['success' => true, 'id' => $id, 'message' => 'Mitarbeiter angelegt']);
} catch (PDOException $e) {
    if ($e->errorInfo[1] == 1062) { // Duplicate entry
        http_response_code(409);
        echo json_encode(['success' => false, 'error' => 'E-Mail existiert bereits']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}