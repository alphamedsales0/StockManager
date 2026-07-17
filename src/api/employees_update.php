<?php
// employees_update.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: PUT, OPTIONS");
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/database_connect.php';

$id = $_GET['id'] ?? 0;
if (!$id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID fehlt']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$name = $input['name'] ?? '';
$email = $input['email'] ?? '';
$password = $input['password'] ?? null; // null = kein neues Passwort

if (!$name || !$email) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Name und E-Mail sind erforderlich']);
    exit;
}

try {
    $updates = "name = ?, email = ?";
    $params = [$name, $email];
    if ($password) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $updates .= ", password_hash = ?";
        $params[] = $hashed;
    }
    $params[] = $id;

    $stmt = $pdo->prepare("UPDATE users SET $updates, updated_at = NOW() WHERE id = ? AND role = 'employee'");
    $stmt->execute($params);
    if ($stmt->rowCount() == 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Mitarbeiter nicht gefunden oder keine Änderungen']);
        exit;
    }
    echo json_encode(['success' => true, 'message' => 'Mitarbeiter aktualisiert']);
} catch (PDOException $e) {
    if ($e->errorInfo[1] == 1062) {
        http_response_code(409);
        echo json_encode(['success' => false, 'error' => 'E-Mail existiert bereits']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}