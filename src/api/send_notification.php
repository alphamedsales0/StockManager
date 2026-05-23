<?php
// send_notification.php
ob_clean(); // Vorherigen Puffer löschen
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

require_once __DIR__ . '/database_connect.php';

$input = json_decode(file_get_contents('php://input'), true);
$ticketId = $input['ticket_id'] ?? null;
$subject = $input['subject'] ?? 'Statusänderung Ihres Tickets';
$message = $input['message'] ?? '';

if (!$ticketId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ticket_id required']);
    exit;
}

try {
    // Kundendaten aus Ticket laden
    $stmt = $pdo->prepare("SELECT customer_data FROM form_submissions WHERE id = ?");
    $stmt->execute([$ticketId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) throw new Exception('Ticket nicht gefunden');
    
    $customer = json_decode($row['customer_data'], true);
    $to = $customer['email'] ?? null;
    
    if (!$to) {
        echo json_encode(['success' => false, 'error' => 'Keine Kunden-E-Mail vorhanden']);
        exit;
    }
    
    // E-Mail senden (hier mit PHP mail() oder SMTP)
    $headers = "From: noreply@deine-domain.de\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $success = mail($to, $subject, $message, $headers);
    
    // In einer Produktivumgebung besser SMTP (PHPMailer) verwenden.
    
    echo json_encode(['success' => $success]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}