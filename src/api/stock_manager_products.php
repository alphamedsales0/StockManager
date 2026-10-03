<?php
// api/stock_manager_products.php
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');
}
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    exit(0);
}
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/marken_helper.php';

// ============================================================
// Helpers globaux
// ============================================================
function makeSlug(string $s): string {
    $s = str_replace(['ä','ö','ü','Ä','Ö','Ü','ß'], ['ae','oe','ue','Ae','Oe','Ue','ss'], $s);
    $s = strtolower(trim($s));
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-');
}
function boolOrNull(array $arr, array $keys) {
    foreach ($keys as $k) if (array_key_exists($k, $arr)) return !empty($arr[$k]) ? 1 : 0;
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
    return is_numeric($v) ? (float)$v : null;
}
function intOrNull($v) {
    if ($v === '' || $v === null || $v === false) return null;
    return is_numeric($v) ? (int)$v : null;
}
function normalizeValue($v) {
    if (is_array($v)) return isset($v['name']) ? (string)$v['name'] : '';
    return (string)$v;
}

// ============================================================
// GET — liste tous les articles
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $dbh->query("SELECT * FROM artikel ORDER BY id DESC");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll()], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ============================================================
// POST — création
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !isset($input['article'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Données du produit manquantes']);
        exit;
    }

    $article   = $input['article'];
    $specifics = $input['specifics'] ?? [];
    $shipping  = $input['shipping']  ?? [];
    $images    = $input['images']    ?? [];

    // Normalisation
    $article['category']     = normalizeValue($article['category'] ?? '');
    $article['article_type'] = normalizeValue($article['article_type'] ?? '');

    // Champs requis
    foreach (['name','brand','category','price','main_image','article_type','article_number'] as $f) {
        if (empty($article[$f])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => "Champ requis manquant : $f"]);
            exit;
        }
    }

    try {
        $dbh->beginTransaction();

        // ============================================================
        // ✅ OPTION B — Marque garantie
        // ============================================================
        $markeCanonique = markeEnsureExists($dbh, $article['brand']);
        if ($markeCanonique === null) {
            throw new Exception("Marke ist erforderlich.");
        }

        // ---------- Specs JSON ----------
        $flatBoolKeys = ['spez_hat_ekg','spez_hat_bluetooth','spez_hat_wifi',
                         'spez_hat_touchscreen','spez_ist_klappbar','spez_hat_rueckenlehne'];
        $flatTextKeys = ['spez_display_typ','spez_widerstandstyp'];
        $spezJson = buildSpezifikationenJson($specifics, array_merge($flatBoolKeys, $flatTextKeys));

        // ============================================================
        // 1. INSERT artikel
        // ============================================================
        $sql = "INSERT INTO artikel (
                    bezeichnung, slug, marke,
                    kategorie_schluessel, unterkategorie_schluessel,
                    artikeltyp, artikelnummer,
                    preis, angebotspreis, waehrung, hauptbild,
                    garantie_jahre, belastbarkeit, stromversorgung, anwendungsbereich,
                    beschreibung, auf_lager, ist_neu, ist_angebot, bestseller,
                    ist_gebraucht, zustand, gebraucht_preis, gebraucht_beschreibung,
                    spezifikationen, varianten,
                    variante_standard_farbe, variante_standard_artikelnummer
                ) VALUES (
                    :bezeichnung, :slug, :marke,
                    :kategorie_schluessel, :unterkategorie_schluessel,
                    :artikeltyp, :artikelnummer,
                    :preis, :angebotspreis, :waehrung, :hauptbild,
                    :garantie_jahre, :belastbarkeit, :stromversorgung, :anwendungsbereich,
                    :beschreibung, :auf_lager, :ist_neu, :ist_angebot, :bestseller,
                    :ist_gebraucht, :zustand, :gebraucht_preis, :gebraucht_beschreibung,
                    :spezifikationen, :varianten,
                    :variante_standard_farbe, :variante_standard_artikelnummer
                )";

        $stmt = $dbh->prepare($sql);
        $stmt->execute([
            ':bezeichnung'       => $article['name'],
            ':slug'              => makeSlug($article['name']),
            ':marke'             => $markeCanonique,

            ':kategorie_schluessel'     => $article['category'],
            ':unterkategorie_schluessel'=> $article['subcategory'] ?? null,

            ':artikeltyp'        => $article['article_type'],
            ':artikelnummer'     => $article['article_number'],

            ':preis'             => (float)$article['price'],
            ':angebotspreis'     => numOrNull($article['offer_price'] ?? null),
            ':waehrung'          => !empty($article['currency']) ? $article['currency'] : 'EUR',
            ':hauptbild'         => $article['main_image'],

            // ✅ NOT NULL DEFAULT 2
            ':garantie_jahre'    => intOrNull($article['warranty_years'] ?? null) ?? 2,

            ':belastbarkeit'     => $article['weight_capacity'] ?? null,
            ':stromversorgung'   => $article['power_supply'] ?? null,
            ':anwendungsbereich' => $article['application_area'] ?? null,
            ':beschreibung'      => $article['description'] ?? null,

            ':auf_lager'         => !empty($article['in_stock']) ? 1 : 0,
            ':ist_neu'           => !empty($article['is_new']) ? 1 : 0,
            ':ist_angebot'       => !empty($article['is_offer']) ? 1 : 0,
            ':bestseller'        => !empty($article['best_seller']) ? 1 : 0,

            ':ist_gebraucht'     => !empty($article['is_used']) ? 1 : 0,
            ':zustand'           => !empty($article['condition']) ? $article['condition'] : null,
            ':gebraucht_preis'   => numOrNull($article['used_price'] ?? null),
            ':gebraucht_beschreibung' => !empty($article['used_description']) ? $article['used_description'] : null,

            ':spezifikationen'   => $spezJson,
            ':varianten'         => !empty($article['variants'])
                                        ? json_encode($article['variants'], JSON_UNESCAPED_UNICODE)
                                        : null,

            ':variante_standard_farbe' => $article['color'] ?? null,
            ':variante_standard_artikelnummer' => $article['article_number']
        ]);

        $articleId = (int)$dbh->lastInsertId();

        // ============================================================
        // 2. Images galerie
        // ============================================================
        if (!empty($images)) {
            $ins = $dbh->prepare("INSERT INTO artikel_bilder
                                  (artikel_id, bild_url, bild_typ, bild_reihenfolge)
                                  VALUES (?, ?, ?, ?)");
            foreach ($images as $i => $img) {
                if (empty($img['url'])) continue;

                $typ = $img['type'] ?? 'galerie';
                if ($typ === 'main')    $typ = 'haupt';
                if ($typ === 'gallery') $typ = 'galerie';
                if (!in_array($typ, ['haupt','galerie','detail'], true)) $typ = 'galerie';

                $ins->execute([
                    $articleId,
                    $img['url'],
                    $typ,
                    $img['image_order'] ?? ($i + 1)
                ]);
            }
        }

        // ============================================================
        // 3. versandregeln (⚠️ 3 colonnes NOT NULL)
        // ============================================================
        if (!empty($shipping)) {
            $ins = $dbh->prepare("INSERT INTO versandregeln
                                  (artikel_id, versandkosten, kostenlos_ab, versandart, lieferzeit_tage)
                                  VALUES (?, ?, ?, ?, ?)");
            $ins->execute([
                $articleId,

                // versandkosten : NULL autorisé
                numOrNull($shipping['shipping_cost'] ?? null),

                // ✅ NOT NULL DEFAULT 50.00
                numOrNull($shipping['free_shipping_threshold'] ?? null) ?? 50.00,

                // ✅ NOT NULL DEFAULT 'Standard'
                !empty($shipping['shipping_method'])
                    ? trim($shipping['shipping_method'])
                    : 'Standard',

                // ✅ NOT NULL DEFAULT 3
                intOrNull($shipping['estimated_delivery_days'] ?? null) ?? 3,
            ]);
        }

        $dbh->commit();
        echo json_encode([
            'success'        => true,
            'message'        => 'Produit créé avec succès',
            'article_id'     => $articleId,
            'article_number' => $article['article_number'],
            'marke'          => $markeCanonique
        ], JSON_UNESCAPED_UNICODE);

    } catch (PDOException $e) {
        if ($dbh->inTransaction()) $dbh->rollBack();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error'   => 'Erreur SQL : ' . $e->getMessage(),
            'code'    => $e->getCode()
        ], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        if ($dbh->inTransaction()) $dbh->rollBack();
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Méthode non autorisée'], JSON_UNESCAPED_UNICODE);