<?php
// add_comment.php
ob_clean();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

require_once __DIR__ . '/database_connect.php';

$input = json_decode(file_get_contents('php://input'), true);
$ticketId = $input['ticket_id'] ?? null;
$text     = $input['text'] ?? '';
$author   = $input['author'] ?? 'System';
$type     = $input['type'] ?? 'comment';   // 'comment', 'status', 'internal_note'
$source   = $input['source'] ?? 'form';

if (!$ticketId || !$text) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ticket_id and text required']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO comments (ticket_id, source, author, text, type) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$ticketId, $source, $author, $text, $type]);
    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}