<?php
// update_assignee.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/database_connect.php';

if (!isset($pdo) || !$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

$rawInput = file_get_contents('php://input');
if (empty($rawInput)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Empty request body']);
    exit;
}

$input = json_decode($rawInput, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON: ' . json_last_error_msg()]);
    exit;
}

$ticketId    = $input['ticket_id'] ?? null;
$assigneeId  = $input['assignee_id'] ?? null;      // falls ID gesendet wird
$assigneeName = $input['assignee_name'] ?? null;   // falls Name direkt gesendet wird
$source      = $input['source'] ?? 'form';
$changedBy   = $input['changed_by'] ?? 'Admin';

if (!$ticketId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ticket_id required']);
    exit;
}

try {
    $table = ($source === 'angebot') ? 'angebot_requests' : 'form_submissions';

    // 1. Name ermitteln: entweder direkt übergeben oder aus users-Tabelle
    $finalAssigneeName = null;
    if ($assigneeName) {
        $finalAssigneeName = $assigneeName;
    } elseif ($assigneeId) {
        $stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
        $stmt->execute([$assigneeId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $finalAssigneeName = $user['name'] ?? null;
        if (!$finalAssigneeName) {
            throw new Exception('Benutzer nicht gefunden');
        }
    }

    // 2. Update durchführen
    $stmt = $pdo->prepare("UPDATE $table SET assigned_to = :assignee, last_updated_by = :changedBy, updated_at = NOW() WHERE id = :id");
    $stmt->execute([':assignee' => $finalAssigneeName, ':changedBy' => $changedBy, ':id' => $ticketId]);

    // 3. Kommentar für Timeline (Fremdschlüssel-Constraint muss entfernt sein!)
    $commentText = "Bearbeiter zugewiesen: " . ($finalAssigneeName ?: 'Niemand');
    $stmtComment = $pdo->prepare("INSERT INTO comments (ticket_id, source, author, text, type) VALUES (?, ?, ?, ?, 'status')");
    $stmtComment->execute([$ticketId, $source, $changedBy, $commentText]);

    echo json_encode(['success' => true, 'assignee_name' => $finalAssigneeName]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}