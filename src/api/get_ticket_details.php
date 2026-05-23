<?php
// get_ticket_details.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/database_connect.php';

if (!isset($pdo) || $pdo === null) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

try {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
    $ref = isset($_GET['ref']) ? trim($_GET['ref']) : null;
    
    if (!$id && !$ref) {
        throw new Exception('ID or reference number required');
    }
    
    $sql = "SELECT * FROM form_submissions WHERE ";
    $params = [];
    if ($id) {
        $sql .= "id = :id";
        $params[':id'] = $id;
    } else {
        $sql .= "reference_number = :ref";
        $params[':ref'] = $ref;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$row) {
        throw new Exception('Ticket not found');
    }
    
    $customer = json_decode($row['customer_data'], true);
    $formData = json_decode($row['form_data'], true);
    
    $response = [
        'success' => true,
        'ticket' => [
            'id' => $row['id'],
            'reference_number' => $row['reference_number'],
            'form_type' => $row['form_type'],
            'submission_date' => $row['submission_date'],
            'status' => $row['status'],
            'kundennummer' => $row['kundennummer'],
            'customer' => $customer,
            'form_data' => $formData,
            'notes' => $row['notes']
        ]
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>