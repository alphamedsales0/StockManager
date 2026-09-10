<?php
// employees_list.php
// Liste tous les employés avec leurs infos principales + adresse primaire
// Dernière mise à jour : 2025-09-10
// ✅ Adapté à la structure : employees.uid + tables enfants avec mitarbeiter_uid

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/database_connect.php';

try {
    // Filtres optionnels (query string)
    $search     = trim($_GET['search'] ?? '');
    $department = trim($_GET['abteilung'] ?? '');
    $position   = trim($_GET['position'] ?? '');

    $sql = "
        SELECT
            u.id            AS benutzer_id,
            u.uid           AS user_uid,
            u.email,
            u.username,
            u.role,
            u.is_active,
            e.id            AS mitarbeiter_id,
            e.uid           AS employee_uid,
            e.mitarbeiter_nummer,
            e.vorname,
            e.nachname,
            e.telefon,
            e.mobil,
            e.position,
            e.abteilung,
            e.einstellungsdatum,
            e.geburtsdatum,
            e.foto_pfad,
            ea.strasse,
            ea.hausnummer,
            ea.plz,
            ea.stadt,
            ea.land
        FROM users u
        INNER JOIN employees e ON u.id = e.benutzer_id
        LEFT JOIN employees_addresses ea
               ON ea.mitarbeiter_uid = e.uid
              AND ea.adresstyp = 'primär'
              AND ea.ist_aktiv = 1
        WHERE 1=1
    ";

    $params = [];

    // Filtre recherche (nom, prénom, email, numéro)
    if ($search !== '') {
        $sql .= " AND (
            e.vorname LIKE ? OR
            e.nachname LIKE ? OR
            u.email LIKE ? OR
            e.mitarbeiter_nummer LIKE ?
        )";
        $like = '%' . $search . '%';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    // Filtre département
    if ($department !== '') {
        $sql .= " AND e.abteilung = ?";
        $params[] = $department;
    }

    // Filtre position
    if ($position !== '') {
        $sql .= " AND e.position = ?";
        $params[] = $position;
    }

    $sql .= " ORDER BY e.nachname ASC, e.vorname ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Normaliser les chemins photo (URL absolue si possible)
    foreach ($employees as &$emp) {
        if (!empty($emp['foto_pfad'])) {
            $emp['photo'] = $emp['foto_pfad'];          // clé attendue par le front
        } else {
            $emp['photo'] = null;
        }
    }
    unset($emp);

    echo json_encode([
        'success'   => true,
        'count'     => count($employees),
        'employees' => $employees
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    file_put_contents(
        __DIR__ . '/debug.log',
        date('Y-m-d H:i:s') . " LIST EXCEPTION: " . $e->getMessage() . "\n",
        FILE_APPEND
    );
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}