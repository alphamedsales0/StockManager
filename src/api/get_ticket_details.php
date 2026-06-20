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
    $id     = isset($_GET['id']) ? (int)$_GET['id'] : null;
    $ref    = isset($_GET['ref']) ? trim($_GET['ref']) : null;
    $source = isset($_GET['source']) ? trim($_GET['source']) : 'form';

    if (!$id && !$ref) {
        throw new Exception('ID or reference number required');
    }

    $ticket = null;

    // ----- 1) FORMULAR-TICKET (source = 'form') -----
    if ($source === 'form') {
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

        if ($row) {
            $customer = json_decode($row['customer_data'], true) ?: [];
            $formData = json_decode($row['form_data'], true) ?: [];

            // Falls keine form_data-Spalte existiert, können Sie hier manuell die Felder
            // aus anderen Spalten zusammensetzen – passen Sie das Ihrer Struktur an.

            $ticket = [
                'id'               => (int)$row['id'],
                'source'           => 'form',
                'reference_number' => $row['reference_number'],
                'created_at'       => $row['submission_date'],
                'updated_at'       => $row['updated_at'] ?? $row['submission_date'],
                'status'           => $row['status'],
                'assigned_to'      => $row['assigned_to'] ?? null,
                'last_updated_by'  => $row['last_updated_by'] ?? null,
                'due_date'         => $row['due_date'] ?? null,
                'form_type'        => $row['form_type'],
                'customer'         => $customer,
                'form_data'        => $formData,
                'comments'         => [], // später über separaten Endpunkt laden
            ];
        }
    }

    // ----- 2) ANGEBOTSANFRAGE (source = 'angebot') -----
    elseif ($source === 'angebot') {
        $sql = "SELECT * FROM angebot_requests WHERE ";
        $params = [];
        if ($id) {
            $sql .= "id = :id";
            $params[':id'] = $id;
        } else {
            $sql .= "reference = :ref";
            $params[':ref'] = $ref;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            // Kundendaten aus den Spalten der Angebotstabelle
            $customer = [
                'firstname' => $row['firstname'],
                'lastname'  => $row['lastname'],
                'company'   => $row['company'],
                'email'     => $row['email'],
                'phone'     => $row['phone'],
            ];

            // Formulardaten (alle felder, die im Vue-Template erwartet werden)
            $formData = [
                'firstname'        => $row['firstname'],
                'lastname'         => $row['lastname'],
                'company'          => $row['company'],
                'email'            => $row['email'],
                'phone'            => $row['phone'],
                'desired_date'     => $row['desired_date'] ?? null,
                'total_quantity'   => (int)($row['total_quantity'] ?? 0),
                'message'          => $row['message'] ?? '',
                'include_shipping' => (bool)($row['include_shipping'] ?? false),
                'include_vat'      => (bool)($row['include_vat'] ?? false),
                'newsletter'       => (bool)($row['newsletter'] ?? false),
                'cart_items'       => json_decode($row['cart_items'] ?? '[]', true) ?: [],
                'cart_subtotal'    => (float)($row['cart_subtotal'] ?? 0),
                'cart_discount'    => (float)($row['cart_discount'] ?? 0),
                'cart_shipping'    => (float)($row['cart_shipping'] ?? 0),
                'cart_total'       => (float)($row['cart_total'] ?? 0),
            ];

            $ticket = [
                'id'               => (int)$row['id'],
                'source'           => 'angebot',
                'reference_number' => $row['reference'],
                'created_at'       => $row['created_at'],
                'updated_at'       => $row['updated_at'] ?? $row['created_at'],
                'status'           => $row['status'],
                'assigned_to'      => $row['assigned_to'] ?? null,
                'last_updated_by'  => $row['last_updated_by'] ?? null,
                'due_date'         => $row['due_date'] ?? null,
                'form_type'        => 'angebot',
                'customer'         => $customer,
                'form_data'        => $formData,
                'comments'         => [],
            ];
        }
    }

    // ----- Antwort -----
    if ($ticket) {
        echo json_encode(['success' => true, 'ticket' => $ticket], JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Ticket not found']);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>