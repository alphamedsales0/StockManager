<?php
// add_ticket_time.php
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
$ticketId    = $input['ticket_id'] ?? null;
$hours       = $input['hours'] ?? null;
$description = $input['description'] ?? '';

// ✅ Le nom vient de la session
$userName    = currentUserName();

if (!$ticketId || !$hours) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ticket_id and hours required']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO time_entries (ticket_id, user_name, hours, description, entry_date)
        VALUES (?, ?, ?, ?, CURDATE())
    ");
    $stmt->execute([$ticketId, $userName, $hours, $description]);

    echo json_encode([
        'success'   => true,
        'entry_id'  => $pdo->lastInsertId(),
        'user_name' => $userName
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}