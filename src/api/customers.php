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
    $sixMonthsAgo = date('Y-m-d H:i:s', strtotime('-6 months'));
    $sql = "
        SELECT 
            k.id,
            CONCAT(COALESCE(pd.vorname, ''), ' ', COALESCE(pd.nachname, '')) AS name,
            MAX(ka.letzte_anmeldung) AS letzte_anmeldung
        FROM kunden k
        LEFT JOIN kunden_personendaten pd ON k.id = pd.kunde_id
        LEFT JOIN kunden_authentifizierung ka ON k.id = ka.kunde_id
        GROUP BY k.id
        ORDER BY k.id ASC
    ";
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $customers = [];
    foreach ($rows as $row) {
        $name = trim(preg_replace('/\s+/', ' ', $row['name']));
        if (empty($name)) {
            $name = 'Kunde #' . $row['id'];
        }
        $active = (!empty($row['letzte_anmeldung']) && $row['letzte_anmeldung'] > $sixMonthsAgo);
        $customers[] = [
            'id'     => (int)$row['id'],
            'name'   => $name,
            'active' => $active
        ];
    }

    echo json_encode([
        'success' => true,
        'data'    => $customers
    ], JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Datenbankfehler: ' . $e->getMessage()
    ]);
}