<?php
// add_ticket_comment.php
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

if (!isset($pdo) || !$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'DB connection failed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON']);
    exit;
}

$ticketId = (int)($input['ticket_id'] ?? 0);
$text     = trim($input['text'] ?? '');
$source   = trim($input['source'] ?? 'form');
$type     = trim($input['type'] ?? 'comment');

// ✅ Le nom vient de la session
$author   = currentUserName();

if (!$ticketId || empty($text)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ticket_id and text required']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO comments (ticket_id, source, author, text, type, created_at)
        VALUES (:ticket_id, :source, :author, :text, :type, NOW())
    ");
    $stmt->execute([
        ':ticket_id' => $ticketId,
        ':source'    => $source,
        ':author'    => $author,
        ':text'      => $text,
        ':type'      => $type,
    ]);

    $newId = (int)$pdo->lastInsertId();

    echo json_encode([
        'success'    => true,
        'comment_id' => $newId,
        'author'     => $author,
        'message'    => 'Kommentar gespeichert'
    ]);
} catch (PDOException $e) {
    error_log('add_ticket_comment.php PDO Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'DB Error: ' . $e->getMessage()]);
}