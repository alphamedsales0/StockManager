<?php
// api/duplicate_product.php
require_once __DIR__ . '/permission_helper.php';
applyCors();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/database_connect.php';
$dbh = $pdo;

$input = json_decode(file_get_contents('php://input'), true);
$sourceId = (int)($input['product_id'] ?? 0);

if (!$sourceId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'product_id erforderlich']);
    exit;
}

// ============================================================
// Helpers
// ============================================================
function makeSlug(string $s): string {
    $s = str_replace(['ä','ö','ü','Ä','Ö','Ü','ß'], ['ae','oe','ue','Ae','Oe','Ue','ss'], $s);
    $s = strtolower(trim($s));
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-');
}

function addVerlauf(PDO $dbh, ?int $artikelId, ?string $artikelNummer, string $aktion, ?string $feld = null, ?string $alter = null, ?string $neuer = null, ?string $beschreibung = null): void {
    try {
        $stmt = $dbh->prepare("
            INSERT INTO artikel_verlauf
                (artikel_id, artikel_nummer, benutzer, aktion, feld, alter_wert, neuer_wert, beschreibung)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $benutzer = $_SERVER['PHP_AUTH_USER'] ?? ($_SERVER['REMOTE_USER'] ?? 'System');
        $stmt->execute([$artikelId, $artikelNummer, $benutzer, $aktion, $feld, $alter, $neuer, $beschreibung]);
    } catch (Exception $e) {
        error_log('addVerlauf failed: ' . $e->getMessage());
    }
}

try {
    $dbh->beginTransaction();

    // ============================================================
    // 1. Charger le produit source
    // ============================================================
    $stmt = $dbh->prepare("SELECT * FROM artikel WHERE id = ?");
    $stmt->execute([$sourceId]);
    $src = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$src) {
        throw new Exception('Produkt nicht gefunden');
    }

    // ============================================================
    // 2. Générer un nouvel artikelnummer unique
    // ============================================================
    $baseNumber = $src['artikelnummer'];
    $newNumber = $baseNumber . '-COPY-' . time();
    $check = $dbh->prepare("SELECT id FROM artikel WHERE artikelnummer = ?");
    $check->execute([$newNumber]);
    if ($check->fetch()) {
        $newNumber = $baseNumber . '-COPY-' . time() . '-' . rand(100, 999);
    }

    // ============================================================
    // 3. Générer un nouveau slug unique
    // ============================================================
    $newName = $src['bezeichnung'] . ' (Kopie)';
    $newSlug = makeSlug($newName);
    $check = $dbh->prepare("SELECT id FROM artikel WHERE slug = ?");
    $check->execute([$newSlug]);
    if ($check->fetch()) {
        $newSlug .= '-' . time();
    }

    // ============================================================
    // 4. Insérer le nouveau produit
    // ============================================================
    $sql = "INSERT INTO artikel (
                artikelnummer, bezeichnung, slug, marke,
                kategorie_schluessel, unterkategorie_schluessel, artikeltyp,
                preis, angebotspreis, waehrung, auf_lager,
                ist_neu, ist_angebot, bestseller,
                ist_gebraucht, zustand, gebraucht_preis, gebraucht_beschreibung,
                hauptbild, garantie_jahre,
                belastbarkeit, stromversorgung, anwendungsbereich, beschreibung,
                spezifikationen, varianten
            ) VALUES (
                :artikelnummer, :bezeichnung, :slug, :marke,
                :kategorie_schluessel, :unterkategorie_schluessel, :artikeltyp,
                :preis, :angebotspreis, :waehrung, :auf_lager,
                :ist_neu, :ist_angebot, :bestseller,
                :ist_gebraucht, :zustand, :gebraucht_preis, :gebraucht_beschreibung,
                :hauptbild, :garantie_jahre,
                :belastbarkeit, :stromversorgung, :anwendungsbereich, :beschreibung,
                :spezifikationen, :varianten
            )";

    $stmt = $dbh->prepare($sql);
    $stmt->execute([
        ':artikelnummer' => $newNumber,
        ':bezeichnung' => $newName,
        ':slug' => $newSlug,
        ':marke' => $src['marke'],
        ':kategorie_schluessel' => $src['kategorie_schluessel'],
        ':unterkategorie_schluessel' => $src['unterkategorie_schluessel'],
        ':artikeltyp' => $src['artikeltyp'],
        ':preis' => $src['preis'],
        ':angebotspreis' => $src['angebotspreis'],
        ':waehrung' => $src['waehrung'],
        ':auf_lager' => 0,                        // la copie n'est pas en stock par défaut
        ':ist_neu' => $src['ist_neu'],
        ':ist_angebot' => $src['ist_angebot'],
        ':bestseller' => 0,                       // pas bestseller par défaut
        ':ist_gebraucht' => $src['ist_gebraucht'],
        ':zustand' => $src['zustand'],
        ':gebraucht_preis' => $src['gebraucht_preis'],
        ':gebraucht_beschreibung' => $src['gebraucht_beschreibung'],
        ':hauptbild' => $src['hauptbild'],
        ':garantie_jahre' => $src['garantie_jahre'],
        ':belastbarkeit' => $src['belastbarkeit'],
        ':stromversorgung' => $src['stromversorgung'],
        ':anwendungsbereich' => $src['anwendungsbereich'],
        ':beschreibung' => $src['beschreibung'],
        ':spezifikationen' => $src['spezifikationen'],
        ':varianten' => $src['varianten'],
    ]);

    $newId = (int)$dbh->lastInsertId();

    // ============================================================
    // 5. Copier les images
    // ============================================================
    $stmt = $dbh->prepare("
        SELECT bild_url, bild_typ, bild_reihenfolge, farbe_schluessel
        FROM artikel_bilder
        WHERE artikel_id = ?
    ");
    $stmt->execute([$sourceId]);
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($images) {
        $ins = $dbh->prepare("
            INSERT INTO artikel_bilder
                (artikel_id, bild_url, bild_typ, bild_reihenfolge, farbe_schluessel)
            VALUES (?, ?, ?, ?, ?)
        ");
        foreach ($images as $img) {
            $ins->execute([
                $newId,
                $img['bild_url'],
                $img['bild_typ'],
                $img['bild_reihenfolge'],
                $img['farbe_schluessel']
            ]);
        }
    }

    // ============================================================
    // 6. Copier les règles de livraison
    // ============================================================
    $stmt = $dbh->prepare("
        SELECT versandkosten, kostenlos_ab, versandart, lieferzeit_tage
        FROM versandregeln
        WHERE artikel_id = ?
    ");
    $stmt->execute([$sourceId]);
    $ship = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($ship) {
        $ins = $dbh->prepare("
            INSERT INTO versandregeln
                (artikel_id, versandkosten, kostenlos_ab, versandart, lieferzeit_tage)
            VALUES (?, ?, ?, ?, ?)
        ");
        $ins->execute([
            $newId,
            $ship['versandkosten'],
            $ship['kostenlos_ab'],
            $ship['versandart'],
            $ship['lieferzeit_tage']
        ]);
    }

    // ============================================================
    // 7. Log dupliqué
    // ============================================================
    addVerlauf(
        $dbh,
        $newId,
        $newNumber,
        'duplicate',
        null,
        null,
        null,
        "Dupliziert von Produkt ID {$sourceId} ({$src['bezeichnung']} / {$baseNumber})"
    );

    // Log côté source (facultatif mais utile)
    addVerlauf(
        $dbh,
        $sourceId,
        $baseNumber,
        'duplicate',
        null,
        null,
        null,
        "Neue Kopie erstellt: {$newNumber} ({$newName})"
    );

    $dbh->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Produkt dupliziert',
        'new_id' => $newId,
        'new_artikelnummer' => $newNumber,
        'new_name' => $newName
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    if ($dbh->inTransaction()) $dbh->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'DB error: ' . $e->getMessage()]);
} catch (Exception $e) {
    if ($dbh->inTransaction()) $dbh->rollBack();
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}