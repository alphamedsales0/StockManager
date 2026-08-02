<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$host_name = 'db5018574434.hosting-data.io';
$database = 'dbs14737411';
$user_name = 'dbu2173288';
$password = 'cc4ef!NQfm4UAFs';

try {
    $dbh = new PDO("mysql:host=$host_name; dbname=$database; charset=utf8mb4", $user_name, $password);
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['product_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'product_id fehlt']);
        exit;
    }
    $productId = (int)$input['product_id'];

    // Prüfen, ob das Produkt existiert
    $stmt = $dbh->prepare("SELECT id FROM articles WHERE id = ?");
    $stmt->execute([$productId]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Produkt nicht gefunden']);
        exit;
    }

    // Löschen – dank ON DELETE CASCADE werden alle abhängigen Datensätze automatisch gelöscht
    $stmt = $dbh->prepare("DELETE FROM articles WHERE id = ?");
    $stmt->execute([$productId]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Datenbankfehler: ' . $e->getMessage()]);
}