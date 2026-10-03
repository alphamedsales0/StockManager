<?php
// api/get_product_details_stock.php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/bild_helper.php';

try {
    $identifier     = isset($_GET['id']) ? trim($_GET['id']) : null;
    $articleNumber  = isset($_GET['article_number']) ? trim($_GET['article_number']) : null;

    if ($identifier && !$articleNumber && !is_numeric($identifier)) {
        $articleNumber = $identifier;
        $identifier = null;
    }
    if (!$identifier && !$articleNumber) {
        echo json_encode(['success' => false, 'error' => 'ID oder article_number erforderlich']);
        exit;
    }

    // 1. Récupère article + shipping
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

    // 2. Spécificités : colonnes plates + JSON
    $specifics = [];
    foreach (['spez_display_typ','spez_widerstandstyp',
              'spez_hat_ekg','spez_hat_bluetooth','spez_hat_wifi',
              'spez_hat_touchscreen','spez_ist_klappbar','spez_hat_rueckenlehne'] as $k) {
        if (isset($art[$k]) && $art[$k] !== null && $art[$k] !== '') {
            $specifics[$k] = $art[$k];
        }
    }
    if (!empty($art['spezifikationen'])) {
        $json = json_decode($art['spezifikationen'], true);
        if (is_array($json)) $specifics = array_merge($json, $specifics);
    }

    // 3. Images galerie
    $imgStmt = $dbh->prepare("SELECT id, bild_url, bild_typ, bild_reihenfolge, farbe_schluessel
                              FROM artikel_bilder
                              WHERE artikel_id = ?
                              ORDER BY bild_reihenfolge ASC, id ASC");
    $imgStmt->execute([$art['id']]);
    $images = [];
    foreach ($imgStmt->fetchAll(PDO::FETCH_ASSOC) as $img) {
        $url = bildMitExtension($img['bild_url']);
        if ($url) {
            $images[] = [
                'id'    => (int)$img['id'],
                'url'   => $url,
                'type'  => $img['bild_typ'] ?: 'gallery',
                'order' => (int)$img['bild_reihenfolge']
            ];
        }
    }

    // 4. Réponse compatible avec EditProduct.vue / ProductForm.vue
    echo json_encode([
        'success' => true,
        'product' => [
            'article' => [
                'name'             => $art['bezeichnung'],
                'brand'            => $art['marke'],
                'category'         => $art['kategorie_schluessel'],
                'subcategory'      => $art['unterkategorie_schluessel'],
                'price'            => (float)$art['preis'],
                'offer_price'      => $art['angebotspreis'] !== null ? (float)$art['angebotspreis'] : null,
                'currency'         => $art['waehrung'] ?: 'EUR',
                'main_image'       => $art['hauptbild'] ? bildMitExtension($art['hauptbild']) : null,
                'article_type'     => $art['artikeltyp'],
                'article_number'   => $art['artikelnummer'],
                'color'            => $art['variante_standard_farbe'],
                'warranty_years'   => $art['garantie_jahre'] !== null ? (int)$art['garantie_jahre'] : null,
                'weight_capacity'  => $art['belastbarkeit'],
                'power_supply'     => $art['stromversorgung'],
                'application_area' => $art['anwendungsbereich'],
                'in_stock'         => (bool)$art['auf_lager'],
                'is_new'           => (bool)$art['ist_neu'],
                'is_offer'         => (bool)$art['ist_angebot'],
                'best_seller'      => (bool)$art['bestseller'],
                'description'      => $art['beschreibung']
            ],
            'specifics' => $specifics,
            'shipping'  => [
                'shipping_cost'            => $art['versandkosten']    !== null ? (float)$art['versandkosten']    : null,
                'free_shipping_threshold'  => $art['kostenlos_ab']     !== null ? (float)$art['kostenlos_ab']     : null,
                'shipping_method'          => $art['versandart'],
                'estimated_delivery_days'  => $art['lieferzeit_tage']  !== null ? (int)$art['lieferzeit_tage']  : null
            ],
            'images' => $images
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
