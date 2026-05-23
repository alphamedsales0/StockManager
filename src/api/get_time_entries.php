<?php
// get_time_entries.php
ob_clean(); // Vorherigen Puffer löschen
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

require_once __DIR__ . '/database_connect.php';

$ticketId = $_GET['ticket_id'] ?? null;
if (!$ticketId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ticket_id required']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, user_name as user, hours, description, entry_date as date FROM time_entries WHERE ticket_id = ? ORDER BY entry_date DESC");
    $stmt->execute([$ticketId]);
    $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'time_entries' => $entries]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}