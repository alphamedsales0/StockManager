<?php
// update_ticket_due_date.php
ob_clean();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

require_once __DIR__ . '/database_connect.php';
require_once __DIR__ . '/permission_helper.php';

// ✅ Contrôle d'accès : Admin + Office uniquement
requireTicketWriteAccess();

$input = json_decode(file_get_contents('php://input'), true);
$ticketId = $input['ticket_id'] ?? null;
$dueDate  = $input['due_date'] ?? null;
$source   = $input['source'] ?? 'form';

if (!$ticketId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ticket_id required']);
    exit;
}

try {
    // ✅ Gérer les deux sources (form_submissions / angebot_requests)
    $table = ($source === 'angebot') ? 'angebot_requests' : 'form_submissions';

    $stmt = $pdo->prepare("UPDATE $table SET due_date = :due_date, updated_at = NOW() WHERE id = :id");
    $stmt->execute([':due_date' => $dueDate, ':id' => $ticketId]);

    // Log dans le commentaire
    $changedBy = currentUserName();
    $commentText = $dueDate
        ? "Fälligkeitsdatum gesetzt: " . date('d.m.Y', strtotime($dueDate))
        : "Fälligkeitsdatum entfernt";
    $stmtComment = $pdo->prepare("INSERT INTO comments (ticket_id, source, author, text, type) VALUES (?, ?, ?, ?, 'status')");
    $stmtComment->execute([$ticketId, $source, $changedBy, $commentText]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}