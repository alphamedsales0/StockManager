<?php
// api/overviewcards.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

require_once __DIR__ . '/database_connect.php';

try {
    // ----------------------------------------------
    // 1. PRODUKTE (articles)
    // ----------------------------------------------
    $stmt = $pdo->query("SELECT COUNT(*) FROM articles");
    $totalProducts = (int)$stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM articles WHERE in_stock = 1");
    $inStockCount = (int)$stmt->fetchColumn();

    $stmt = $pdo->query("SELECT SUM(price) FROM articles WHERE in_stock = 1");
    $totalStockValue = (float)($stmt->fetchColumn() ?? 0);

    $averageProductValue = ($inStockCount > 0) ? round($totalStockValue / $inStockCount, 2) : 0;

    // ----------------------------------------------
    // 2. KUNDEN (kunden)
    // ----------------------------------------------
    $stmt = $pdo->query("SELECT COUNT(*) FROM kunden");
    $totalCustomers = (int)$stmt->fetchColumn();

    // Aktive Kunden: zuletzt angemeldet in den letzten 6 Monaten
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT k.id)
        FROM kunden k
        LEFT JOIN kunden_authentifizierung ka ON k.id = ka.kunde_id
        WHERE ka.letzte_anmeldung > DATE_SUB(NOW(), INTERVAL 6 MONTH)
    ");
    $stmt->execute();
    $activeCustomers = (int)$stmt->fetchColumn();

    // ----------------------------------------------
    // 3. ANGEBOTE (angebot_requests)
    // ----------------------------------------------
    $stmt = $pdo->query("SELECT COUNT(*) FROM angebot_requests");
    $totalQuotes = (int)$stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM angebot_requests WHERE status = 'pending'");
    $pendingQuotes = (int)$stmt->fetchColumn();

    // ----------------------------------------------
    // 4. BESTELLUNGEN (orders) – optional
    // ----------------------------------------------
    $totalOrders = 0;
    $pendingOrders = 0;
    $completedOrders = 0;
    $totalRevenue = 0;

    $tableExists = $pdo->query("SHOW TABLES LIKE 'orders'")->rowCount() > 0;
    if ($tableExists) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM orders");
        $totalOrders = (int)$stmt->fetchColumn();

        $stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ('pending', 'processing')");
        $pendingOrders = (int)$stmt->fetchColumn();

        $stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'completed'");
        $completedOrders = (int)$stmt->fetchColumn();

        $stmt = $pdo->query("SELECT SUM(total) FROM orders WHERE status = 'completed'");
        $totalRevenue = (float)($stmt->fetchColumn() ?? 0);
    }

    // ----------------------------------------------
    // 5. RÜCKGABE
    // ----------------------------------------------
    $response = [
        'success' => true,
        'data' => [
            'totalProducts'        => $totalProducts,
            'inStockCount'         => $inStockCount,
            'totalStockValue'      => $totalStockValue,
            'averageProductValue'  => $averageProductValue,
            'totalCustomers'       => $totalCustomers,
            'activeCustomers'      => $activeCustomers,
            'totalQuotes'          => $totalQuotes,
            'pendingQuotes'        => $pendingQuotes,
            'totalOrders'          => $totalOrders,
            'pendingOrders'        => $pendingOrders,
            'completedOrders'      => $completedOrders,
            'totalRevenue'         => $totalRevenue,
        ]
    ];

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Datenbankfehler: ' . $e->getMessage()
    ]);
}