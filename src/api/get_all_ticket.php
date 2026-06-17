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
    // Parameter auslesen
    $statusFilter = isset($_GET['status']) ? $_GET['status'] : null;
    $formTypeFilter = isset($_GET['form_type']) ? $_GET['form_type'] : null;
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
    $offset = ($page - 1) * $limit;

    // Basis-SQL für Count und Hauptabfrage
    $baseSql = "SELECT 
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

    // Filter hinzufügen
    if ($statusFilter) {
        $baseSql .= " AND status = :status";
        $params[':status'] = $statusFilter;
    }
    if ($formTypeFilter) {
        $baseSql .= " AND form_type = :form_type";
        $params[':form_type'] = $formTypeFilter;
    }

    // Count-Abfrage (Gesamtanzahl für Paginierung)
    $countSql = "SELECT COUNT(*) as total FROM form_submissions WHERE 1=1";
    if ($statusFilter) {
        $countSql .= " AND status = :status";
    }
    if ($formTypeFilter) {
        $countSql .= " AND form_type = :form_type";
    }
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($total / $limit);

    // Hauptabfrage mit Sortierung und Limit/Offset
    $sql = $baseSql . " ORDER BY submission_date DESC LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);

    // Bindungen für Limit und Offset (Integer)
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);

    // Bindungen für Filter (Strings)
    foreach ($params as $key => &$val) {
        if ($key !== ':limit' && $key !== ':offset') {
            $stmt->bindParam($key, $val, PDO::PARAM_STR);
        }
    }

    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Tickets aufbereiten
    $tickets = [];
    foreach ($rows as $row) {
        $customer = json_decode($row['customer_data'], true);
        $company = $customer['company'] ?? $row['kundennummer'] ?? 'Privatkunde';

        $subjectMap = [
            'service_request' => 'Serviceanforderung',
            'maintenance' => 'Wartungsvertrag',
            'installation' => 'Installation',
            'ersatzteile' => 'Ersatzteile'  // neu hinzugefügt
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

    // JSON-Ausgabe
    echo json_encode([
        'success' => true,
        'tickets' => $tickets,
        'totalPages' => $totalPages,
        'currentPage' => $page,
        'total' => $total
    ]);

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