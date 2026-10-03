<?php
// add_ticket_note.php
require_once __DIR__ . '/permission_helper.php';
applyCors();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

require_once __DIR__ . '/database_connect.php';
require_once __DIR__ . '/permission_helper.php';

// ✅ Contrôle d'accès : Admin + Office uniquement
requireTicketWriteAccess();

$input = json_decode(file_get_contents('php://input'), true);
$ticketId = $input['ticket_id'] ?? null;
$note     = $input['note'] ?? null;

// ✅ Le nom vient de la session
$author   = currentUserName();

if (!$ticketId || !$note) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ticket_id and note required']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO internal_notes (ticket_id, author, note) VALUES (?, ?, ?)");
    $stmt->execute([$ticketId, $author, $note]);

    echo json_encode([
        'success' => true,
        'note_id' => $pdo->lastInsertId(),
        'author'  => $author
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}