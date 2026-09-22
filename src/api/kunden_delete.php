<?php
// kunden_delete.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: DELETE, POST, OPTIONS");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/database_connect.php';

$id = $_GET['id'] ?? 0;
if (!$id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID fehlt']);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM kunden WHERE id = ?");
    $stmt->execute([(int)$id]);

    if ($stmt->rowCount() == 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Kunde nicht gefunden']);
        exit;
    }

    echo json_encode(['success' => true, 'message' => 'Kunde gelöscht']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}