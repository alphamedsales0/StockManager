<?php
// add_comment.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

require_once __DIR__ . '/database_connect.php';

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
$author   = trim($input['author'] ?? 'Admin');
$source   = trim($input['source'] ?? 'form');
$type     = trim($input['type'] ?? 'comment');

if (!$ticketId || empty($text)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ticket_id and text required']);
    exit;
}

try {
    // INSERT mit allen Spalten – wenn source nicht existiert, schlägt es fehl (wir fangen den Fehler ab)
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
        'message'    => 'Kommentar gespeichert'
    ]);
} catch (PDOException $e) {
    // Fehler protokollieren (z.B. wenn Spalte 'source' fehlt)
    error_log('add_comment.php PDO Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'DB Error: ' . $e->getMessage()]);
}