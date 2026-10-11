<?php
// api/create_order.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/config/database.php'; // votre connexion PDO

$input = json_decode(file_get_contents('php://input'), true);
$order = $input['order'] ?? null;

if (!$order) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No order data']);
    exit;
}

try {
    // Démarrer une transaction
    $dbh->beginTransaction();

    // Générer un numéro de commande unique
    $orderNumber = 'ORD-' . date('Ymd') . '-' . str_pad(rand(0, 99999), 5, '0', STR_PAD_LEFT);

    // ============================================================
    // 1. INSÉRER LA COMMANDE
    // ============================================================
    $sql = "INSERT INTO orders (
                order_number, customer_email, customer_name,
                shipping_address, billing_address,
                shipping_method, payment_method, payment_intent_id,
                subtotal, shipping_cost, total, status, created_at
            ) VALUES (
                :order_number, :email, :name,
                :shipping, :billing,
                :shipping_method, :payment_method, :payment_intent_id,
                :subtotal, :shipping_cost, :total, 'pending', NOW()
            )";

    $stmt = $dbh->prepare($sql);
    $stmt->execute([
        ':order_number'      => $orderNumber,
        ':email'             => $order['shippingAddress']['email'],
        ':name'              => $order['shippingAddress']['firstName'] . ' ' . $order['shippingAddress']['lastName'],
        ':shipping'          => json_encode($order['shippingAddress'], JSON_UNESCAPED_UNICODE),
        ':billing'           => json_encode($order['billingAddress'], JSON_UNESCAPED_UNICODE),
        ':shipping_method'   => $order['shippingMethod'],
        ':payment_method'    => $order['paymentMethod'],
        ':payment_intent_id' => $order['paymentIntentId'] ?? null,
        ':subtotal'          => $order['subtotal'],
        ':shipping_cost'     => $order['shippingCost'],
        ':total'             => $order['total']
    ]);

    $orderId = $dbh->lastInsertId();

    // ============================================================
    // 2. INSÉRER LES ARTICLES (avec Farbe + Zustand)
    // ============================================================
    foreach ($order['items'] as $item) {
        $sql = "INSERT INTO order_items (
                    order_id, article_number, name, quantity, price, total,
                    farbe, farbe_bezeichnung, ist_gebraucht, zustand
                ) VALUES (
                    :order_id, :article_number, :name, :quantity, :price, :total,
                    :farbe, :farbe_bezeichnung, :ist_gebraucht, :zustand
                )";

        $stmt = $dbh->prepare($sql);
        $stmt->execute([
            ':order_id'          => $orderId,
            ':article_number'    => $item['article_number'] ?? $item['id'],
            ':name'              => $item['name'],
            ':quantity'          => $item['quantity'],
            ':price'             => $item['price'],
            ':total'             => $item['price'] * $item['quantity'],
            ':farbe'             => $item['farbe'] ?? null,
            ':farbe_bezeichnung' => $item['farbe_bezeichnung'] ?? null,
            ':ist_gebraucht'     => !empty($item['ist_gebraucht']) ? 1 : 0,
            ':zustand'           => $item['zustand'] ?? null
        ]);
    }

    $dbh->commit();

    echo json_encode([
        'success' => true,
        'orderNumber' => $orderNumber,
        'message' => 'Ihre Bestellung wurde erfolgreich aufgegeben'
    ]);

} catch (Exception $e) {
    $dbh->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}