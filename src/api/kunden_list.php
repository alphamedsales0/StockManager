<?php
// kunden_list.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/database_connect.php';

try {
    $search = trim($_GET['search'] ?? '');
    $personType = trim($_GET['person_type'] ?? '');
    $kundengruppe = trim($_GET['kundengruppe'] ?? '');

    $sql = "
        SELECT
            k.id, k.kundennummer, k.person_type, k.profilbild,
            p.anrede, p.vorname, p.nachname,
            g.firma, g.zusatz, g.kontakt,
            a.email, a.email_verifiziert,
            i.steuernummer, i.ust_id_nr,
            kl.kundengruppe, kl.branche,
            adr.strasse, adr.hausnummer, adr.plz, adr.ort, adr.land
        FROM kunden k
        LEFT JOIN kunden_personendaten p ON p.kunde_id = k.id
        LEFT JOIN kunden_gewerbedaten g ON g.kunde_id = k.id
        LEFT JOIN kunden_authentifizierung a ON a.kunde_id = k.id
        LEFT JOIN kunden_identifikation i ON i.kunde_id = k.id
        LEFT JOIN kunden_klassifizierung kl ON kl.kunde_id = k.id
        LEFT JOIN kunden_adressen adr ON adr.kunde_id = k.id AND adr.ist_standard = 1
        WHERE 1=1
    ";
    $params = [];

    if ($search !== '') {
        $sql .= " AND (k.kundennummer LIKE ? OR p.vorname LIKE ? OR p.nachname LIKE ? OR g.firma LIKE ? OR a.email LIKE ?)";
        $like = '%' . $search . '%';
        array_push($params, $like, $like, $like, $like, $like);
    }
    if ($personType !== '') {
        $sql .= " AND k.person_type = ?";
        $params[] = $personType;
    }
    if ($kundengruppe !== '') {
        $sql .= " AND kl.kundengruppe = ?";
        $params[] = $kundengruppe;
    }

    $sql .= " ORDER BY k.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $kunden = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'count' => count($kunden), 'kunden' => $kunden], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    file_put_contents(__DIR__ . '/debug.log', date('Y-m-d H:i:s') . " KUNDEN LIST ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}