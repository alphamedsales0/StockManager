<?php
// get_comments.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// Fehler unterdrücken, aber in JSON ausgeben
error_reporting(E_ALL);
ini_set('display_errors', 0); // Keine HTML-Fehler
ini_set('log_errors', 1);

require_once __DIR__ . '/database_connect.php';

$ticketId = isset($_GET['ticket_id']) ? intval($_GET['ticket_id']) : 0;
$source   = isset($_GET['source']) ? $_GET['source'] : 'form';

if (!$ticketId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ticket_id required']);
    exit;
}

try {
    // Prüfen, ob die Tabelle comments existiert
    $stmt = $pdo->query("SHOW TABLES LIKE 'comments'");
    if ($stmt->rowCount() == 0) {
        // Tabelle existiert nicht – leeres Array zurückgeben
        echo json_encode(['success' => true, 'comments' => []]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM comments WHERE ticket_id = ? AND source = ? ORDER BY created_at DESC");
    $stmt->execute([$ticketId, $source]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'comments' => $comments]);
} catch (Exception $e) {
    // Fehler in JSON ausgeben
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>