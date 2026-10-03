<?php
// api/get_farben.php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/database_connect.php';
$dbh = $pdo;

try {
    $stmt = $dbh->query("
        SELECT id, schluessel, bezeichnung, hex_code, ist_standard, sortierung
        FROM farben
        WHERE ist_aktiv = 1
        ORDER BY sortierung ASC, bezeichnung ASC
    ");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'items'   => $items
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}