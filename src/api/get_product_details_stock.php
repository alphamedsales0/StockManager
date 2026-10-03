<?php
// api/get_product_details_stock.php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/database_connect.php';
require_once __DIR__ . '/bild_helper.php';
$dbh = $pdo;

try {
    // ============================================================
    // PARAMÈTRES
    // ============================================================
    $identifier    = isset($_GET['id']) ? trim($_GET['id']) : null;
    $articleNumber = isset($_GET['article_number']) ? trim($_GET['article_number']) : null;

    if ($identifier && !$articleNumber && !is_numeric($identifier)) {
        $articleNumber = $identifier;
        $identifier = null;
    }
    if (!$identifier && !$articleNumber) {
        echo json_encode(['success' => false, 'error' => 'ID oder article_number erforderlich']);
        exit;
    }

    // ============================================================
    // 1. ARTICLE + VERSAND
    // ============================================================
    $sql = "SELECT a.*,
                   sr.versandkosten, sr.kostenlos_ab, sr.versandart, sr.lieferzeit_tage
            FROM artikel a
            LEFT JOIN versandregeln sr ON a.id = sr.artikel_id
            WHERE " . ($identifier ? "a.id = :id" : "a.artikelnummer = :an");

    $stmt = $dbh->prepare($sql);
    $stmt->execute($identifier ? [':id' => (int)$identifier] : [':an' => $articleNumber]);
    $art = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$art) {
        echo json_encode(['success' => false, 'error' => 'Produkt nicht gefunden']);
        exit;
    }

    // ============================================================
    // 2. SPÉCIFICITÉS — colonnes plates + JSON
    // ============================================================
    $specifics = [];
    foreach ([
        'spez_display_typ','spez_widerstandstyp',
        'spez_hat_ekg','spez_hat_bluetooth','spez_hat_wifi',
        'spez_hat_touchscreen','spez_ist_klappbar','spez_hat_rueckenlehne'
    ] as $k) {
        if (isset($art[$k]) && $art[$k] !== null && $art[$k] !== '') {
            $specifics[$k] = $art[$k];
        }
    }
    if (!empty($art['spezifikationen'])) {
        $json = json_decode($art['spezifikationen'], true);
        if (is_array($json)) $specifics = array_merge($json, $specifics);
    }

    // ============================================================
    // 3. HAUPTBILD — résolution via bildMitExtension()
    // ============================================================
    $hauptbild = null;
    if (!empty($art['hauptbild'])) {
        $hauptbild = bildMitExtension($art['hauptbild']) ?: $art['hauptbild'];
    }

    // ============================================================
    // 4. IMAGES DE GALERIE — avec farbe_schluessel
    // ============================================================
    $imgStmt = $dbh->prepare("
        SELECT id, bild_url, bild_typ, bild_reihenfolge, farbe_schluessel
        FROM artikel_bilder
        WHERE artikel_id = ?
        ORDER BY bild_reihenfolge ASC, id ASC
    ");
    $imgStmt->execute([$art['id']]);

    $images = [];
    foreach ($imgStmt->fetchAll(PDO::FETCH_ASSOC) as $img) {
        $url = bildMitExtension($img['bild_url']);
        if (!$url) {
            $url = $img['bild_url'];  // fallback : chemin brut
        }
        $images[] = [
            'id'                => (int)$img['id'],
            'url'               => $url,
            'bild_url'          => $url,   // alias
            'type'              => $img['bild_typ'] ?: 'galerie',
            'order'             => (int)$img['bild_reihenfolge'],
            'farbe_schluessel'  => $img['farbe_schluessel']
        ];
    }

    // ============================================================
    // 5. COULEURS — artikel_farben + farben
    // ============================================================
    $farbenStmt = $dbh->prepare("
        SELECT
            f.id,
            f.schluessel,
            f.bezeichnung,
            f.hex_code,
            f.ist_standard,
            f.sortierung AS farbe_sortierung,
            af.artikelnummer_variante,
            af.preis AS farbe_preis,
            af.bild_url AS farbe_bild,
            af.sortierung AS af_sortierung
        FROM artikel_farben af
        INNER JOIN farben f ON f.id = af.farbe_id
        WHERE af.artikel_id = ?
          AND af.ist_aktiv = 1
          AND f.ist_aktiv = 1
        ORDER BY af.sortierung ASC, f.sortierung ASC
    ");
    $farbenStmt->execute([$art['id']]);
    $farben = $farbenStmt->fetchAll(PDO::FETCH_ASSOC);

    $farbenClean = array_map(function($f) use ($art) {
        $bildUrl = null;
        if (!empty($f['farbe_bild'])) {
            $bildUrl = bildMitExtension($f['farbe_bild']) ?: $f['farbe_bild'];
        }
        return [
            'id'             => (int)$f['id'],
            'schluessel'     => $f['schluessel'],
            'bezeichnung'    => $f['bezeichnung'],
            'hex_code'       => $f['hex_code'],
            'ist_standard'   => (bool)$f['ist_standard'],
            'artikelnummer'  => $f['artikelnummer_variante'] ?: $art['artikelnummer'],
            'preis'          => $f['farbe_preis'] !== null ? (float)$f['farbe_preis'] : (float)$art['preis'],
            'bild_url'       => $bildUrl
        ];
    }, $farben);

    // ============================================================
    // 6. RÉPONSE — structure attendue par EditProduct.vue / ProductView.vue
    // ============================================================
    echo json_encode([
        'success' => true,
        'product' => [
            'article' => [
                // Identifiants
                'name'             => $art['bezeichnung'],
                'brand'            => $art['marke'],
                'category'         => $art['kategorie_schluessel'],
                'subcategory'      => $art['unterkategorie_schluessel'],
                'article_type'     => $art['artikeltyp'],
                'article_number'   => $art['artikelnummer'],
                'slug'             => $art['slug'],

                // Prix
                'price'            => (float)$art['preis'],
                'offer_price'      => $art['angebotspreis'] !== null ? (float)$art['angebotspreis'] : null,
                'currency'         => $art['waehrung'] ?: 'EUR',

                // Image principale
                'main_image'       => $hauptbild,

                // ✅ Couleur standard (schluessel de la couleur, ex: "weiss")
                'color'            => $art['variante_standard_farbe'],

                // Caractéristiques communes
                'warranty_years'   => $art['garantie_jahre'] !== null ? (int)$art['garantie_jahre'] : null,
                'weight_capacity'  => $art['belastbarkeit'],
                'power_supply'     => $art['stromversorgung'],
                'application_area' => $art['anwendungsbereich'],

                // Statuts
                'in_stock'         => (bool)$art['auf_lager'],
                'is_new'           => (bool)$art['ist_neu'],
                'is_offer'         => (bool)$art['ist_angebot'],
                'best_seller'      => (bool)$art['bestseller'],
                'is_used'          => (bool)$art['ist_gebraucht'],
                'condition'        => $art['zustand'],
                'used_price'       => $art['gebraucht_preis'] !== null ? (float)$art['gebraucht_preis'] : null,
                'used_description' => $art['gebraucht_beschreibung'],

                // Description
                'description'      => $art['beschreibung']
            ],
            'specifics' => $specifics,
            'shipping'  => [
                'shipping_cost'           => $art['versandkosten']   !== null ? (float)$art['versandkosten']   : null,
                'free_shipping_threshold' => $art['kostenlos_ab']    !== null ? (float)$art['kostenlos_ab']    : 50.00,
                'shipping_method'         => $art['versandart']      ?: 'Standard',
                'estimated_delivery_days' => $art['lieferzeit_tage'] !== null ? (int)$art['lieferzeit_tage']  : 3
            ],
            'images' => $images,
            'farben' => $farbenClean
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}