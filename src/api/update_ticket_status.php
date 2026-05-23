<?php
// update_ticket_status.php
ob_clean(); // Vorherigen Puffer löschen
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

require_once __DIR__ . '/database_connect.php';

$input = json_decode(file_get_contents('php://input'), true);
$ticketId = $input['ticket_id'] ?? null;
$newStatus = $input['status'] ?? null;
$notifyCustomer = $input['notify_customer'] ?? false;

if (!$ticketId || !$newStatus) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ticket_id and status required']);
    exit;
}

try {
    // Status aktualisieren
    $stmt = $pdo->prepare("UPDATE form_submissions SET status = :status WHERE id = :id");
    $stmt->execute([':status' => $newStatus, ':id' => $ticketId]);
    
    $response = ['success' => true];
    
    // Optionale Benachrichtigung
    if ($notifyCustomer) {
        $stmt = $pdo->prepare("SELECT customer_data FROM form_submissions WHERE id = ?");
        $stmt->execute([$ticketId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $customer = json_decode($row['customer_data'], true);
        if (!empty($customer['email'])) {
            $statusText = translateStatus($newStatus);
            $subject = "Ihr Ticket wurde auf '$statusText' gesetzt";
            $message = "Guten Tag,\n\nDer Status Ihres Tickets wurde geändert zu: $statusText.\n\nViele Grüße,\nIhr Support-Team";
            mail($customer['email'], $subject, $message, "From: noreply@deine-domain.de");
            $response['notification_sent'] = true;
        }
    }
    
    echo json_encode($response);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function translateStatus($status) {
    $map = ['pending' => 'In Bearbeitung', 'in_progress' => 'In Prüfung', 'completed' => 'Abgeschlossen', 'cancelled' => 'Storniert'];
    return $map[$status] ?? $status;
}