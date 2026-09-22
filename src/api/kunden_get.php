<?php
// kunden_get.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

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
            k.id, k.kundennummer, k.person_type, k.profilbild,
            k.created_at, k.updated_at,
            p.anrede, p.vorname, p.nachname,
            g.firma, g.zusatz, g.kontakt, g.hrb_register_nummer, g.hrb_gerichtsstand,
            a.email, a.email_verifiziert, a.letzte_anmeldung, a.agb_accepted, a.datenschutz_accepted,
            i.steuernummer, i.ust_id_nr, i.auslandskunden, i.eg_ausland,
            kl.kundengruppe, kl.branche,
            r.zahlungsweise, r.versandart, r.zahlungsziel, r.gesamtrabatt
        FROM kunden k
        LEFT JOIN kunden_personendaten p ON p.kunde_id = k.id
        LEFT JOIN kunden_gewerbedaten g ON g.kunde_id = k.id
        LEFT JOIN kunden_authentifizierung a ON a.kunde_id = k.id
        LEFT JOIN kunden_identifikation i ON i.kunde_id = k.id
        LEFT JOIN kunden_klassifizierung kl ON kl.kunde_id = k.id
        LEFT JOIN kunden_rechnungsdaten r ON r.kunde_id = k.id
        WHERE k.id = ?
    ");
    $stmt->execute([(int)$id]);
    $kunde = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$kunde) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Kunde nicht gefunden']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM kunden_adressen WHERE kunde_id = ? ORDER BY ist_standard DESC, id ASC");
    $stmt->execute([$id]);
    $kunde['adressen'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT * FROM kunden_kommunikation WHERE kunde_id = ? ORDER BY ist_standard DESC, id ASC");
    $stmt->execute([$id]);
    $kunde['kommunikation'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT * FROM kunden_bankverbindung WHERE kunde_id = ? ORDER BY ist_standard DESC, id ASC");
    $stmt->execute([$id]);
    $kunde['bankverbindungen'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT * FROM kunden_preisgruppen WHERE kunde_id = ? ORDER BY prioritaet DESC, id ASC");
    $stmt->execute([$id]);
    $kunde['preisgruppen'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT * FROM kunden_artikelrabatte WHERE kunde_id = ? ORDER BY stufe ASC");
    $stmt->execute([$id]);
    $kunde['artikelrabatte'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT * FROM kunden_mahngebuehren WHERE kunde_id = ? ORDER BY stufe ASC");
    $stmt->execute([$id]);
    $kunde['mahngebuehren'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT * FROM kunden_skonto WHERE kunde_id = ? ORDER BY stufe ASC");
    $stmt->execute([$id]);
    $kunde['skonto'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT * FROM kunden_bemerkungen WHERE kunde_id = ? ORDER BY erstellt_am DESC");
    $stmt->execute([$id]);
    $kunde['bemerkungen'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT * FROM kunden_zusatzfelder WHERE kunde_id = ? ORDER BY id ASC");
    $stmt->execute([$id]);
    $kunde['zusatzfelder'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'kunde' => $kunde], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    file_put_contents(__DIR__ . '/debug.log', date('Y-m-d H:i:s') . " KUNDEN GET ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}