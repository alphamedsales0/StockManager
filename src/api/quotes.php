<?php
// Gestion CORS (identique à votre get_products.php qui fonctionne)
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');
}

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
        header("Access-Control-Allow-Methods: GET, OPTIONS");
    }
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    }
    exit(0);
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/database_connect.php';

try {
    $sql = "SELECT 
                id, reference, firstname, lastname, company, email, phone,
                desired_date, total_quantity, message, include_shipping, include_vat,
                newsletter, cart_subtotal, cart_discount, cart_shipping, cart_total,
                status, created_at
            FROM angebot_requests
            ORDER BY created_at DESC";
    $stmt = $pdo->query($sql);
    $quotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $formatted = [];
    foreach ($quotes as $q) {
        $formatted[] = [
            'id'            => (int)$q['id'],
            'reference'     => $q['reference'],
            'customerName'  => trim($q['firstname'] . ' ' . $q['lastname']),
            'company'       => $q['company'],
            'email'         => $q['email'],
            'phone'         => $q['phone'],
            'desiredDate'   => $q['desired_date'],
            'totalQuantity' => (int)$q['total_quantity'],
            'cartTotal'     => (float)$q['cart_total'],
            'status'        => $q['status'],
            'createdAt'     => $q['created_at']
        ];
    }

    echo json_encode([
        'success' => true,
        'data'    => $formatted
    ], JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Datenbankfehler: ' . $e->getMessage()
    ]);
}