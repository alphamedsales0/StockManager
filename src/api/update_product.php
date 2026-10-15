<?php
// api/update_product.php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/database_connect.php';
require_once __DIR__ . '/marken_helper.php';
$dbh = $pdo;

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['product_id']) || !isset($input['article'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit;
}

$productId    = (int)$input['product_id'];
$articleData  = $input['article'];
$specifics    = $input['specifics']  ?? [];
$shippingData = $input['shipping']   ?? [];
$imagesData   = $input['images']     ?? [];

// ============================================================
// Helpers
// ============================================================
function normalizeValue($v) {
    if (is_array($v)) return isset($v['name']) ? (string)$v['name'] : '';
    return (string)$v;
}

function makeSlug(string $s): string {
    $s = str_replace(['ä','ö','ü','Ä','Ö','Ü','ß'], ['ae','oe','ue','Ae','Oe','Ue','ss'], $s);
    $s = strtolower(trim($s));
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-');
}

function buildSpezifikationenJson(array $specifics, array $excludeKeys): ?string {
    $rest = [];
    foreach ($specifics as $k => $v) {
        if (in_array($k, $excludeKeys, true)) continue;
        if ($v === '' || $v === null) continue;
        $rest[$k] = $v;
    }
    return empty($rest) ? null : json_encode($rest, JSON_UNESCAPED_UNICODE);
}

function numOrNull($v) {
    if ($v === '' || $v === null || $v === false) return null;
    return is_numeric($v) ? (float)$v : null;
}

function intOrNull($v) {
    if ($v === '' || $v === null || $v === false) return null;
    return is_numeric($v) ? (int)$v : null;
}

/**
 * Ajoute une entrée dans artikel_verlauf
 */
function addVerlauf(PDO $dbh, int $artikelId, string $aktion, ?string $feld = null, ?string $alter = null, ?string $neuer = null, ?string $beschreibung = null): void {
    try {
        $stmt = $dbh->prepare("
            INSERT INTO artikel_verlauf (artikel_id, benutzer, aktion, feld, alter_wert, neuer_wert, beschreibung)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $benutzer = $_SERVER['PHP_AUTH_USER'] ?? ($_SERVER['REMOTE_USER'] ?? 'System');
        $stmt->execute([$artikelId, $benutzer, $aktion, $feld, $alter, $neuer, $beschreibung]);
    } catch (Exception $e) {
        error_log('addVerlauf failed: ' . $e->getMessage());
    }
}

// ============================================================
// Normalisation
// ============================================================
$articleData['category']     = normalizeValue($articleData['category'] ?? '');
$articleData['article_type'] = normalizeValue($articleData['article_type'] ?? '');

try {
    $dbh->beginTransaction();

    // ============================================================
    // 0. Charger l'état ACTUEL
    // ============================================================
    $oldStmt = $dbh->prepare("SELECT * FROM artikel WHERE id = ?");
    $oldStmt->execute([$productId]);
    $old = $oldStmt->fetch(PDO::FETCH_ASSOC);

    if (!$old) {
        throw new Exception('Produkt nicht gefunden');
    }

    // ============================================================
    // 1. Marque garantie
    // ============================================================
    $markeCanonique = markeEnsureExists($dbh, $articleData['brand'] ?? '');
    if ($markeCanonique === null) {
        throw new Exception('Marke ist erforderlich.');
    }

    // ============================================================
    // 2. Préparer les valeurs
    // ============================================================
    $flatBoolKeys = ['spez_hat_ekg','spez_hat_bluetooth','spez_hat_wifi',
                     'spez_hat_touchscreen','spez_ist_klappbar','spez_hat_rueckenlehne'];
    $flatTextKeys = ['spez_display_typ','spez_widerstandstyp'];
    $spezJson = buildSpezifikationenJson($specifics, array_merge($flatBoolKeys, $flatTextKeys));

    $values = [
        'bezeichnung'       => $articleData['name'] ?? '',
        'slug'              => makeSlug($articleData['name'] ?? ''),
        'marke'             => $markeCanonique,
        'kategorie_schluessel' => $articleData['category'] ?? '',
        'unterkategorie_schluessel' => $articleData['subcategory'] ?? null,
        'artikeltyp'        => $articleData['article_type'] ?? '',
        'artikelnummer'     => $articleData['article_number'] ?? '',
        'preis'             => (float)($articleData['price'] ?? 0),
        'angebotspreis'     => numOrNull($articleData['offer_price'] ?? null),
        'waehrung'          => !empty($articleData['currency']) ? $articleData['currency'] : 'EUR',
        'hauptbild'         => $articleData['main_image'] ?? '',
        'garantie_jahre'    => intOrNull($articleData['warranty_years'] ?? null) ?? 2,
        'belastbarkeit'     => $articleData['weight_capacity'] ?? null,
        'stromversorgung'   => $articleData['power_supply'] ?? null,
        'anwendungsbereich' => $articleData['application_area'] ?? null,
        'beschreibung'      => $articleData['description'] ?? null,
        'auf_lager'         => !empty($articleData['in_stock']) ? 1 : 0,
        'ist_neu'           => !empty($articleData['is_new']) ? 1 : 0,
        'ist_angebot'       => !empty($articleData['is_offer']) ? 1 : 0,
        'bestseller'        => !empty($articleData['best_seller']) ? 1 : 0,
        'ist_gebraucht'     => !empty($articleData['is_used']) ? 1 : 0,
        'zustand'           => !empty($articleData['condition']) ? $articleData['condition'] : null,
        'gebraucht_preis'   => numOrNull($articleData['used_price'] ?? null),
        'gebraucht_beschreibung' => !empty($articleData['used_description']) ? $articleData['used_description'] : null,
        'spezifikationen'   => $spezJson,
        'varianten'         => !empty($articleData['variants'])
                                    ? json_encode($articleData['variants'], JSON_UNESCAPED_UNICODE)
                                    : null,
        'variante_standard_farbe' => $articleData['color'] ?? null,
        'variante_standard_artikelnummer' => $articleData['article_number'] ?? null,
    ];

    // ============================================================
    // 3. Log des changements
    // ============================================================
    $labelMap = [
        'bezeichnung'        => 'Name',
        'marke'              => 'Marke',
        'kategorie_schluessel' => 'Kategorie',
        'unterkategorie_schluessel' => 'Unterkategorie',
        'artikeltyp'         => 'Artikeltyp',
        'artikelnummer'      => 'Artikelnummer',
        'preis'              => 'Preis',
        'angebotspreis'      => 'Angebotspreis',
        'waehrung'           => 'Währung',
        'hauptbild'          => 'Hauptbild',
        'garantie_jahre'     => 'Garantie',
        'belastbarkeit'      => 'Tragfähigkeit',
        'stromversorgung'    => 'Stromversorgung',
        'anwendungsbereich'  => 'Anwendungsbereich',
        'beschreibung'       => 'Beschreibung',
        'auf_lager'          => 'Auf Lager',
        'ist_neu'            => 'Neu',
        'ist_angebot'        => 'Angebot',
        'bestseller'         => 'Bestseller',
        'ist_gebraucht'      => 'Gebraucht',
        'zustand'            => 'Zustand',
        'gebraucht_preis'    => 'Gebrauchtpreis',
        'gebraucht_beschreibung' => 'Gebraucht-Beschreibung',
        'variante_standard_farbe' => 'Standardfarbe',
    ];

    $changesCount = 0;
    foreach ($values as $field => $newValue) {
        if ($field === 'spezifikationen' || $field === 'varianten') continue;
        if (!array_key_exists($field, $old)) continue;

        $oldValue = $old[$field];
        if ((string)$oldValue !== (string)$newValue) {
            if (($oldValue === null && $newValue === '') || ($oldValue === '' && $newValue === null)) {
                continue;
            }
            $label = $labelMap[$field] ?? $field;
            addVerlauf(
                $dbh,
                $productId,
                'update',
                $label,
                $oldValue !== null ? (string)$oldValue : null,
                $newValue !== null ? (string)$newValue : null,
                "Feld geändert: {$label}"
            );
            $changesCount++;
        }
    }

    addVerlauf(
        $dbh,
        $productId,
        'update',
        null,
        null,
        null,
        $changesCount > 0
            ? "Produkt aktualisiert ({$changesCount} Feld(er) geändert)"
            : "Produkt gespeichert (keine Änderungen)"
    );

    // ============================================================
    // 4. UPDATE artikel
    // ============================================================
    $sql = "UPDATE artikel SET
                bezeichnung         = :bezeichnung,
                slug                = :slug,
                marke               = :marke,
                kategorie_schluessel = :kategorie_schluessel,
                unterkategorie_schluessel = :unterkategorie_schluessel,
                artikeltyp          = :artikeltyp,
                artikelnummer       = :artikelnummer,
                preis               = :preis,
                angebotspreis       = :angebotspreis,
                waehrung            = :waehrung,
                hauptbild           = :hauptbild,
                garantie_jahre      = :garantie_jahre,
                belastbarkeit       = :belastbarkeit,
                stromversorgung     = :stromversorgung,
                anwendungsbereich   = :anwendungsbereich,
                beschreibung        = :beschreibung,
                auf_lager           = :auf_lager,
                ist_neu             = :ist_neu,
                ist_angebot         = :ist_angebot,
                bestseller          = :bestseller,
                ist_gebraucht       = :ist_gebraucht,
                zustand             = :zustand,
                gebraucht_preis     = :gebraucht_preis,
                gebraucht_beschreibung = :gebraucht_beschreibung,
                spezifikationen     = :spezifikationen,
                varianten           = :varianten,
                variante_standard_farbe = :variante_standard_farbe,
                variante_standard_artikelnummer = :variante_standard_artikelnummer
            WHERE id = :id";

    $stmt = $dbh->prepare($sql);
    $stmt->execute(array_merge($values, [':id' => $productId]));

    // ============================================================
    // 5. versandregeln (INSERT ou UPDATE)
    // ============================================================
    $check = $dbh->prepare("SELECT id FROM versandregeln WHERE artikel_id = ?");
    $check->execute([$productId]);
    $exists = (bool)$check->fetch();

    if ($exists) {
        $sql = "UPDATE versandregeln SET
                    versandkosten    = :versandkosten,
                    kostenlos_ab     = :kostenlos_ab,
                    versandart       = :versandart,
                    lieferzeit_tage  = :lieferzeit_tage
                WHERE artikel_id = :artikel_id";
    } else {
        $sql = "INSERT INTO versandregeln
                    (artikel_id, versandkosten, kostenlos_ab, versandart, lieferzeit_tage)
                VALUES (:artikel_id, :versandkosten, :kostenlos_ab, :versandart, :lieferzeit_tage)";
    }

    $stmt = $dbh->prepare($sql);
    $stmt->execute([
        ':artikel_id'      => $productId,
        ':versandkosten'   => numOrNull($shippingData['shipping_cost'] ?? null),
        ':kostenlos_ab'    => numOrNull($shippingData['free_shipping_threshold'] ?? null) ?? 50.00,
        ':versandart'      => !empty($shippingData['shipping_method'])
                                ? trim($shippingData['shipping_method'])
                                : 'Standard',
        ':lieferzeit_tage' => intOrNull($shippingData['estimated_delivery_days'] ?? null) ?? 3,
    ]);

    // ============================================================
    // 6. artikel_bilder — AVEC farbe_schluessel
    // ============================================================
    $keepIds = [];
    foreach ($imagesData as $img) {
        if (!empty($img['id'])) $keepIds[] = (int)$img['id'];
    }

    if (!empty($keepIds)) {
        $ph  = implode(',', array_fill(0, count($keepIds), '?'));
        $del = $dbh->prepare("DELETE FROM artikel_bilder WHERE artikel_id = ? AND id NOT IN ($ph)");
        $del->execute(array_merge([$productId], $keepIds));
    } else {
        $del = $dbh->prepare("DELETE FROM artikel_bilder WHERE artikel_id = ?");
        $del->execute([$productId]);
    }

    $order = 1;
    foreach ($imagesData as $img) {
        if (empty($img['url'])) continue;

        $bildUrl = $img['url'];
        $typ = $img['type'] ?? 'galerie';
        if ($typ === 'main')    $typ = 'haupt';
        if ($typ === 'gallery') $typ = 'galerie';
        if (!in_array($typ, ['haupt','galerie','detail'], true)) $typ = 'galerie';

        // ✅ Récupère la couleur associée à l'image
        $farbeSchluessel = null;
        if (!empty($img['farbe_schluessel'])) {
            $farbeSchluessel = trim((string)$img['farbe_schluessel']);
        } elseif (!empty($img['farbe'])) {              // fallback compat
            $farbeSchluessel = trim((string)$img['farbe']);
        } elseif (!empty($img['color'])) {              // fallback compat
            $farbeSchluessel = trim((string)$img['color']);
        }

        if (!empty($img['id'])) {
            $upd = $dbh->prepare("UPDATE artikel_bilder
                                  SET bild_url = ?, bild_typ = ?, bild_reihenfolge = ?, farbe_schluessel = ?
                                  WHERE id = ? AND artikel_id = ?");
            $upd->execute([$bildUrl, $typ, $order, $farbeSchluessel, (int)$img['id'], $productId]);
        } else {
            $ins = $dbh->prepare("INSERT INTO artikel_bilder
                                  (artikel_id, bild_url, bild_typ, bild_reihenfolge, farbe_schluessel)
                                  VALUES (?, ?, ?, ?, ?)");
            $ins->execute([$productId, $bildUrl, $typ, $order, $farbeSchluessel]);
        }
        $order++;
    }

    $dbh->commit();
    echo json_encode([
        'success' => true,
        'message' => 'Produkt erfolgreich aktualisiert',
        'marke'   => $markeCanonique,
        'changes' => $changesCount
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    if ($dbh->inTransaction()) $dbh->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    if ($dbh->inTransaction()) $dbh->rollBack();
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}