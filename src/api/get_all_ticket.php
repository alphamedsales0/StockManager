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
    $statusFilter   = isset($_GET['status']) ? $_GET['status'] : null;
    $formTypeFilter = isset($_GET['form_type']) ? $_GET['form_type'] : null;
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
    $offset = ($page - 1) * $limit;

    // ----- Hilfsfunktion für die Übersetzung -----
    function translateStatus($status) {
        $map = [
            'pending'     => 'In Bearbeitung',
            'in_progress' => 'In Prüfung',
            'processing'  => 'In Prüfung',   // falls noch vorhanden
            'completed'   => 'Abgeschlossen',
            'cancelled'   => 'Storniert'
        ];
        return $map[$status] ?? $status;
    }

    // ----- ZÄHLEN der Gesamtzahl (für Paginierung) -----
    $total = 0;

    // 1) Count für form_submissions
    $countSqlForm = "SELECT COUNT(*) FROM form_submissions WHERE 1=1";
    $countParamsForm = [];
    if ($statusFilter) {
        $countSqlForm .= " AND status = :status";
        $countParamsForm[':status'] = $statusFilter;
    }
    if ($formTypeFilter && $formTypeFilter !== 'angebot') {
        $countSqlForm .= " AND form_type = :form_type";
        $countParamsForm[':form_type'] = $formTypeFilter;
    } elseif ($formTypeFilter === 'angebot') {
        // dann werden keine form_submissions gezählt
        $countForm = 0;
    }
    if (!isset($countForm)) {
        $stmt = $pdo->prepare($countSqlForm);
        $stmt->execute($countParamsForm);
        $countForm = (int)$stmt->fetchColumn();
    }

    // 2) Count für angebot_requests
    $countSqlAngebot = "SELECT COUNT(*) FROM angebot_requests WHERE 1=1";
    $countParamsAngebot = [];
    if ($statusFilter) {
        $countSqlAngebot .= " AND status = :status";
        $countParamsAngebot[':status'] = $statusFilter;
    }
    if ($formTypeFilter && $formTypeFilter !== 'angebot') {
        // Wenn FormType nicht 'angebot', dann keine Angebotsanfragen
        $countAngebot = 0;
    } else {
        // formTypeFilter ist NULL oder 'angebot' → alle zählen (mit Status-Filter)
    }
    if (!isset($countAngebot)) {
        $stmt = $pdo->prepare($countSqlAngebot);
        $stmt->execute($countParamsAngebot);
        $countAngebot = (int)$stmt->fetchColumn();
    }

    $total = $countForm + $countAngebot;
    $totalPages = ($total > 0) ? ceil($total / $limit) : 1;

    // ----- HAUPTABFRAGE mit UNION -----
    $sql = "
        SELECT * FROM (
            SELECT
                id,
                'form' AS source,
                reference_number AS ref_nr,
                submission_date AS ticket_date,
                form_type AS type,
                customer_data AS customer_json,
                NULL AS firstname,
                NULL AS lastname,
                NULL AS company,
                status,
                kundennummer
            FROM form_submissions
            WHERE 1=1
    ";

    if ($statusFilter) {
        $sql .= " AND status = :status";
    }
    if ($formTypeFilter && $formTypeFilter !== 'angebot') {
        $sql .= " AND form_type = :form_type";
    } elseif ($formTypeFilter === 'angebot') {
        $sql .= " AND 1=0";   // keine form_submissions
    }

    $sql .= "
            UNION ALL
            SELECT
                id,
                'angebot' AS source,
                reference AS ref_nr,
                created_at AS ticket_date,
                'angebot' AS type,
                NULL AS customer_json,
                firstname,
                lastname,
                company,
                status,
                NULL AS kundennummer
            FROM angebot_requests
            WHERE 1=1
    ";

    if ($statusFilter) {
        $sql .= " AND status = :status";
    }
    if ($formTypeFilter && $formTypeFilter !== 'angebot') {
        $sql .= " AND 1=0";   // keine angebot_requests
    }

    $sql .= "
        ) AS combined
        ORDER BY ticket_date DESC
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $pdo->prepare($sql);

    // Parameter binden
    if ($statusFilter) {
        $stmt->bindParam(':status', $statusFilter, PDO::PARAM_STR);
    }
    if ($formTypeFilter && $formTypeFilter !== 'angebot') {
        $stmt->bindParam(':form_type', $formTypeFilter, PDO::PARAM_STR);
    }
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ----- Ergebnis aufbereiten -----
    $tickets = [];
    foreach ($rows as $row) {
        if ($row['source'] === 'form') {
            $customer = json_decode($row['customer_json'], true);
            $company = $customer['company'] ?? $row['kundennummer'] ?? 'Privatkunde';
            $subjectMap = [
                'service_request' => 'Serviceanforderung',
                'maintenance'     => 'Wartungsvertrag',
                'installation'    => 'Installation',
                'ersatzteile'     => 'Ersatzteile'
            ];
            $subject = $subjectMap[$row['type']] ?? ucfirst($row['type']);
            $kundenname = $company;
        } else { // angebot
            $firstname = $row['firstname'];
            $lastname  = $row['lastname'];
            $company   = $row['company'];
            if (!empty($company)) {
                $kundenname = $company;
            } else {
                $kundenname = trim($firstname . ' ' . $lastname);
                if (empty($kundenname)) $kundenname = 'Unbekannt';
            }
            $subject = 'Angebotsanfrage';
        }

        $tickets[] = [
            'id'          => $row['id'],
            'date'        => date('d.m.Y', strtotime($row['ticket_date'])),
            'refNr'       => $row['ref_nr'],
            'name'        => $subject,
            'kundenname'  => $kundenname,
            'status'      => translateStatus($row['status']),
            'raw_status'  => $row['status'],
            'form_type'   => $row['type'],
            'source'      => $row['source']   // wichtig für die Detailnavigation
        ];
    }

    echo json_encode([
        'success'     => true,
        'tickets'     => $tickets,
        'totalPages'  => $totalPages,
        'currentPage' => $page,
        'total'       => $total
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} 