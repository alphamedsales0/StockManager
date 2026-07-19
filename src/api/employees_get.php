<?php
// employees_get.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/database_connect.php';

$id = $_GET['id'] ?? 0;
if (!$id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID fehlt']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT
            u.id AS benutzer_id,
            u.email,
            u.is_active,
            e.*
        FROM users u
        JOIN employees e ON u.id = e.benutzer_id
        WHERE u.id = ? AND u.role = 'employee'
    ");
    $stmt->execute([$id]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$employee) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Mitarbeiter nicht gefunden']);
        exit;
    }

    // Adresses actives
    $stmtAddr = $pdo->prepare("
        SELECT id, adresstyp, strasse, hausnummer, plz, stadt, land, ist_aktiv
        FROM employees_addresses
        WHERE mitarbeiter_id = ? AND ist_aktiv = 1
    ");
    $stmtAddr->execute([$employee['id']]);
    $addresses = $stmtAddr->fetchAll(PDO::FETCH_ASSOC);
    $employee['addresses'] = $addresses;

    unset($employee['password_hash']); // sécurité
    echo json_encode(['success' => true, 'employee' => $employee]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}