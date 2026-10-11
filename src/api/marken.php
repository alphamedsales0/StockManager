<?php
// api/marken.php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/database.php';

try {
    $kategorie  = $_GET['kategorie']  ?? '';
    $artikeltyp = $_GET['artikeltyp'] ?? '';

    $sql = "SELECT DISTINCT marke FROM artikel WHERE marke IS NOT NULL AND marke != '' AND auf_lager = 1";
    $params = [];

    if ($kategorie) {
        $sql .= " AND kategorie_schluessel = :kategorie";
        $params[':kategorie'] = $kategorie;
    }
    if ($artikeltyp) {
        $sql .= " AND artikeltyp = :artikeltyp";
        $params[':artikeltyp'] = $artikeltyp;
    }

    $sql .= " ORDER BY marke";

    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
    $marken = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

    echo json_encode(['success' => true, 'marken' => $marken], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}