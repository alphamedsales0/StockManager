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

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/marken_helper.php';

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

// ---------- Normalisation ----------
function normalizeValue($v) {
    if (is_array($v)) return isset($v['name']) ? (string)$v['name'] : '';
    return (string)$v;
}
$articleData['category']     = normalizeValue($articleData['category'] ?? '');
$articleData['article_type'] = normalizeValue($articleData['article_type'] ?? '');

// ---------- Slug ----------
function makeSlug(string $s): string {
    $s = str_replace(['ä','ö','ü','Ä','Ö','Ü','ß'], ['ae','oe','ue','Ae','Oe','Ue','ss'], $s);
    $s = strtolower(trim($s));
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-');
}

// ---------- Helpers bool/JSON ----------
function boolOrNull(array $arr, array $keys) {
    foreach ($keys as $k) {
        if (array_key_exists($k, $arr)) {
            return !empty($arr[$k]) ? 1 : 0;
        }
    }
    return null;
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
    if (!is_numeric($v)) return null;
    return (float)$v;
}
function intOrNull($v) {
    if ($v === '' || $v === null || $v === false) return null;
    if (!is_numeric($v)) return null;
    return (int)$v;
}

try {
    $dbh->beginTransaction();

    // ============================================================
    // ✅ OPTION B — Marque garantie dans `marken`
    // ============================================================
    $markeCanonique = markeEnsureExists($dbh, $articleData['brand'] ?? '');
    if ($markeCanonique === null) {
        throw new Exception("Marke ist erforderlich.");
    }

    // ============================================================
    // 1. UPDATE artikel
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

    $flatBoolKeys = ['spez_hat_ekg','spez_hat_bluetooth','spez_hat_wifi',
                     'spez_hat_touchscreen','spez_ist_klappbar','spez_hat_rueckenlehne'];
    $flatTextKeys = ['spez_display_typ','spez_widerstandstyp'];
    $spezJson = buildSpezifikationenJson($specifics, array_merge($flatBoolKeys, $flatTextKeys));

    $stmt = $dbh->prepare($sql);
    $stmt->execute([
        ':bezeichnung'       => $articleData['name'] ?? '',
        ':slug'              => makeSlug($articleData['name'] ?? ''),
        ':marke'             => $markeCanonique,
        ':kategorie_schluessel' => $articleData['category'] ?? '',
        ':unterkategorie_schluessel' => $articleData['subcategory'] ?? null,
        ':artikeltyp'        => $articleData['article_type'] ?? '',
        ':artikelnummer'     => $articleData['article_number'] ?? '',

        ':preis'             => (float)($articleData['price'] ?? 0),
        ':angebotspreis'     => numOrNull($articleData['offer_price'] ?? null),
        ':waehrung'          => !empty($articleData['currency']) ? $articleData['currency'] : 'EUR',

        ':hauptbild'         => $articleData['main_image'] ?? '',

        // ✅ NOT NULL DEFAULT 2
        ':garantie_jahre'    => intOrNull($articleData['warranty_years'] ?? null) ?? 2,

        ':belastbarkeit'     => $articleData['weight_capacity'] ?? null,
        ':stromversorgung'   => $articleData['power_supply'] ?? null,
        ':anwendungsbereich' => $articleData['application_area'] ?? null,
        ':beschreibung'      => $articleData['description'] ?? null,

        ':auf_lager'         => !empty($articleData['in_stock']) ? 1 : 0,
        ':ist_neu'           => !empty($articleData['is_new']) ? 1 : 0,
        ':ist_angebot'       => !empty($articleData['is_offer']) ? 1 : 0,
        ':bestseller'        => !empty($articleData['best_seller']) ? 1 : 0,

        ':ist_gebraucht'     => !empty($articleData['is_used']) ? 1 : 0,
        ':zustand'           => !empty($articleData['condition']) ? $articleData['condition'] : null,
        ':gebraucht_preis'   => numOrNull($articleData['used_price'] ?? null),
        ':gebraucht_beschreibung' => !empty($articleData['used_description']) ? $articleData['used_description'] : null,

        ':spezifikationen'   => $spezJson,
        ':varianten'         => !empty($articleData['variants'])
                                    ? json_encode($articleData['variants'], JSON_UNESCAPED_UNICODE)
                                    : null,

        ':variante_standard_farbe' => $articleData['color'] ?? null,
        ':variante_standard_artikelnummer' => $articleData['article_number'] ?? null,

        ':id'                => $productId
    ]);

    // ============================================================
    // 2. versandregeln (⚠️ 3 colonnes NOT NULL)
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

        // versandkosten : NULL autorisé
        ':versandkosten'   => numOrNull($shippingData['shipping_cost'] ?? null),

        // ✅ NOT NULL DEFAULT 50.00
        ':kostenlos_ab'    => numOrNull($shippingData['free_shipping_threshold'] ?? null) ?? 50.00,

        // ✅ NOT NULL DEFAULT 'Standard'
        ':versandart'      => !empty($shippingData['shipping_method'])
                                ? trim($shippingData['shipping_method'])
                                : 'Standard',

        // ✅ NOT NULL DEFAULT 3
        ':lieferzeit_tage' => intOrNull($shippingData['estimated_delivery_days'] ?? null) ?? 3,
    ]);

    // ============================================================
    // 3. artikel_bilder
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
        // ✅ ENUM('haupt','galerie','detail') — mapping depuis le front
        $typ = $img['type'] ?? 'galerie';
        if ($typ === 'main')    $typ = 'haupt';
        if ($typ === 'gallery') $typ = 'galerie';
        if (!in_array($typ, ['haupt','galerie','detail'], true)) $typ = 'galerie';

        if (!empty($img['id'])) {
            $upd = $dbh->prepare("UPDATE artikel_bilder
                                  SET bild_url = ?, bild_typ = ?, bild_reihenfolge = ?
                                  WHERE id = ? AND artikel_id = ?");
            $upd->execute([$bildUrl, $typ, $order, (int)$img['id'], $productId]);
        } else {
            $ins = $dbh->prepare("INSERT INTO artikel_bilder
                                  (artikel_id, bild_url, bild_typ, bild_reihenfolge)
                                  VALUES (?, ?, ?, ?)");
            $ins->execute([$productId, $bildUrl, $typ, $order]);
        }
        $order++;
    }

    $dbh->commit();
    echo json_encode([
        'success' => true,
        'message' => 'Produkt erfolgreich aktualisiert',
        'marke'   => $markeCanonique
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