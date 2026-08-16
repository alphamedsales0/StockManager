<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/database_connect.php';

$input = json_decode(file_get_contents('php://input'), true);
$id = (int)($input['id'] ?? 0);
$name = trim($input['name'] ?? '');
$display_name = trim($input['display_name'] ?? '');
$description = trim($input['description'] ?? '');

if (!$id || !$name || !$display_name) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID, Name und Anzeigename sind erforderlich']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE roles SET name = ?, display_name = ?, description = ? WHERE id = ?");
    $stmt->execute([$name, $display_name, $description, $id]);
    echo json_encode(['success' => true, 'message' => 'Rolle aktualisiert']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}