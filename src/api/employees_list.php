<?php
// employees_list.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header('Content-Type: application/json; charset=utf-8');

// Gestion de la requête OPTIONS (pré-vol CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/database_connect.php';

try {
    $stmt = $pdo->query("
        SELECT
            u.id AS benutzer_id,
            u.email,
            u.is_active,
            e.id AS mitarbeiter_id,
            e.mitarbeiter_nummer,
            e.vorname,
            e.nachname,
            e.telefon,
            e.mobil,
            e.position,
            e.abteilung,
            e.einstellungsdatum,
            ea.strasse,
            ea.hausnummer,
            ea.plz,
            ea.stadt,
            ea.land
        FROM users u
        JOIN employees e ON u.id = e.benutzer_id
        LEFT JOIN employees_addresses ea ON e.id = ea.mitarbeiter_id AND ea.adresstyp = 'primär' AND ea.ist_aktiv = 1
        WHERE u.role = 'employee'
        ORDER BY e.nachname, e.vorname
    ");
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'employees' => $employees]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}