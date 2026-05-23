<?php
// add_internal_note.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

require_once __DIR__ . '/database_connect.php';

$input = json_decode(file_get_contents('php://input'), true);
$ticketId = $input['ticket_id'] ?? null;
$note = $input['note'] ?? null;
$author = $input['author'] ?? 'Admin';

if (!$ticketId || !$note) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ticket_id and note required']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO internal_notes (ticket_id, author, note) VALUES (?, ?, ?)");
    $stmt->execute([$ticketId, $author, $note]);
    echo json_encode(['success' => true, 'note_id' => $pdo->lastInsertId()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}