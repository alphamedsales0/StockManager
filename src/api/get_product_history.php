<?php
// api/get_product_history.php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/database_connect.php';
$dbh = $pdo;

try {
    $artikelId = (int)($_GET['id'] ?? 0);
    if (!$artikelId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'id erforderlich']);
        exit;
    }

    $stmt = $dbh->prepare("
        SELECT id, benutzer, aktion, feld, alter_wert, neuer_wert, beschreibung, erstellt_am
        FROM artikel_verlauf
        WHERE artikel_id = ?
        ORDER BY erstellt_am DESC
        LIMIT 100
    ");
    $stmt->execute([$artikelId]);

    echo json_encode([
        'success' => true,
        'items' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}