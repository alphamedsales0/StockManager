<?php
// get_all_ticket.php
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
    $statusFilter = isset($_GET['status']) ? $_GET['status'] : null;
    $formTypeFilter = isset($_GET['form_type']) ? $_GET['form_type'] : null;
    
    $sql = "SELECT 
                id,
                reference_number,
                form_type,
                submission_date,
                customer_data,
                status,
                kundennummer
            FROM form_submissions
            WHERE 1=1";
    $params = [];
    
    if ($statusFilter) {
        $sql .= " AND status = :status";
        $params[':status'] = $statusFilter;
    }
    if ($formTypeFilter) {
        $sql .= " AND form_type = :form_type";
        $params[':form_type'] = $formTypeFilter;
    }
    
    $sql .= " ORDER BY submission_date DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $tickets = [];
    foreach ($rows as $row) {
        $customer = json_decode($row['customer_data'], true);
        $company = $customer['company'] ?? $row['kundennummer'] ?? 'Privatkunde';
        
        $subjectMap = [
            'service_request' => 'Serviceanforderung',
            'maintenance' => 'Wartungsvertrag',
            'installation' => 'Installation'
        ];
        $subject = $subjectMap[$row['form_type']] ?? ucfirst($row['form_type']);
        
        $tickets[] = [
            'id' => $row['id'],
            'date' => date('d.m.Y', strtotime($row['submission_date'])),
            'refNr' => $row['reference_number'],
            'name' => $subject,
            'kundenname' => $company,
            'status' => translateStatus($row['status']),
            'raw_status' => $row['status'],
            'form_type' => $row['form_type']
        ];
    }
    
    echo json_encode(['success' => true, 'tickets' => $tickets]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function translateStatus($status) {
    $map = [
        'pending' => 'In Bearbeitung',
        'in_progress' => 'In Prüfung',
        'completed' => 'Abgeschlossen',
        'cancelled' => 'Storniert'
    ];
    return $map[$status] ?? $status;
}
?>