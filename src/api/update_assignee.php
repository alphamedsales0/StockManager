<?php
// update_assignee.php (vollständige korrigierte Version)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Preflight (OPTIONS) beantworten, bevor der Request abgewiesen wird
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

error_reporting(E_ALL);
ini_set('display_errors', 0);      // Keine Ausgabe im Browser
ini_set('log_errors', 1);          // Aber ins Server-Log schreiben

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

$ticketId = $input['ticket_id'] ?? null;
$assigneeId = $input['assignee_id'] ?? null;

if (!$ticketId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ticket_id required']);
    exit;
}

try {
    $assigneeName = null;
    if ($assigneeId) {
        $stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
        $stmt->execute([$assigneeId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $assigneeName = $user['name'] ?? null;
    }
    
    $stmt = $pdo->prepare("UPDATE form_submissions SET assigned_to = :assignee WHERE id = :id");
    $stmt->execute([':assignee' => $assigneeName, ':id' => $ticketId]);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>