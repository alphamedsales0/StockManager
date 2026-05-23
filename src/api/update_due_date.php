<?php
// update_due_date.php
ob_clean(); // Vorherigen Puffer löschen
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

require_once __DIR__ . '/database_connect.php';

$input = json_decode(file_get_contents('php://input'), true);
$ticketId = $input['ticket_id'] ?? null;
$dueDate = $input['due_date'] ?? null;

if (!$ticketId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ticket_id required']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE form_submissions SET due_date = :due_date WHERE id = :id");
    $stmt->execute([':due_date' => $dueDate, ':id' => $ticketId]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}