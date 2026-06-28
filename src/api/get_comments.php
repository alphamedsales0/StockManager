<?php
// get_comments.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/database_connect.php';

if (!isset($pdo) || !$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'DB connection failed']);
    exit;
}

$ticketId = isset($_GET['ticket_id']) ? (int)$_GET['ticket_id'] : 0;

if (!$ticketId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ticket_id required']);
    exit;
}

try {
    // Sortierung: neueste zuerst (für die Timeline)
    $stmt = $pdo->prepare("
        SELECT id, source, author, text, type, created_at
        FROM comments
        WHERE ticket_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$ticketId]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success'  => true,
        'comments' => $comments
    ]);
} catch (PDOException $e) {
    error_log('get_comments.php Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}