<?php
// add_time_entry.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

require_once __DIR__ . '/database_connect.php';

$input = json_decode(file_get_contents('php://input'), true);
$ticketId = $input['ticket_id'] ?? null;
$hours = $input['hours'] ?? null;
$description = $input['description'] ?? '';
$userName = $input['user_name'] ?? 'Admin';

if (!$ticketId || !$hours) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ticket_id and hours required']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO time_entries (ticket_id, user_name, hours, description, entry_date) VALUES (?, ?, ?, ?, CURDATE())");
    $stmt->execute([$ticketId, $userName, $hours, $description]);
    echo json_encode(['success' => true, 'entry_id' => $pdo->lastInsertId()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}