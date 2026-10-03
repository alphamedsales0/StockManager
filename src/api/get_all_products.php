<?php
// get_all_products.php
// API endpoint pour récupérer tous les produits avec leurs caractéristiques principales
// Adapté à la nouvelle structure de la table `artikel`

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/bild_helper.php';

try {
    // ============================================================
    // PARAMÈTRES OPTIONNELS (filtres)
    // ============================================================
    $kategorie      = $_GET['kategorie']      ?? '';
    $unterkategorie = $_GET['unterkategorie'] ?? '';
    $artikeltyp     = $_GET['artikeltyp']     ?? '';
    $marke          = $_GET['marke']          ?? '';
    $suche          = $_GET['suche']          ?? '';
    $limit          = isset($_GET['limit'])  ? (int)$_GET['limit']  : 500;
    $offset         = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

    // ============================================================
    // REQUÊTE PRINCIPALE
    // ============================================================
    $sql = "SELECT
                id,
                artikelnummer,
                bezeichnung,
                slug,
                marke,
                kategorie_schluessel,
                unterkategorie_schluessel,
                artikeltyp,
                preis,
                angebotspreis,
                waehrung,
                hauptbild,
                auf_lager,
                ist_neu,
                ist_angebot,
                bestseller,
                garantie_jahre,
                belastbarkeit,
                stromversorgung,
                anwendungsbereich,
                beschreibung,
                spezifikationen,
                varianten,
                spez_display_typ,
                spez_widerstandstyp,
                spez_hat_ekg,
                spez_hat_bluetooth,
                spez_hat_wifi,
                spez_hat_touchscreen,
                spez_ist_klappbar,
                spez_hat_rueckenlehne,
                variante_standard_farbe,
                variante_standard_artikelnummer,
                ist_gebraucht,
                zustand,
                gebraucht_preis,
                gebraucht_beschreibung
            FROM artikel
            WHERE 1 = 1";

    $params = [];

    if ($kategorie !== '' && $kategorie !== 'alle' && $kategorie !== 'all') {
        $sql .= " AND kategorie_schluessel = :kategorie";
        $params[':kategorie'] = $kategorie;
    }
    if ($unterkategorie !== '') {
        $sql .= " AND unterkategorie_schluessel = :unterkategorie";
        $params[':unterkategorie'] = $unterkategorie;
    }
    if ($artikeltyp !== '') {
        $sql .= " AND artikeltyp = :artikeltyp";
        $params[':artikeltyp'] = $artikeltyp;
    }
    if ($marke !== '') {
        $sql .= " AND marke = :marke";
        $params[':marke'] = $marke;
    }
    if ($suche !== '') {
        $sql .= " AND (bezeichnung LIKE :suche
                       OR marke LIKE :suche
                       OR artikelnummer LIKE :suche
                       OR beschreibung LIKE :suche)";
        $params[':suche'] = "%$suche%";
    }

    // ORDER : mêmes règles que artikel.php (best-sellers d'abord)
    $sql .= " ORDER BY ist_gebraucht ASC, bestseller DESC, ist_neu DESC, bezeichnung ASC
              LIMIT $limit OFFSET $offset";

    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    // ============================================================
    // IMAGES DE GALERIE (batch pour éviter N+1)
    // ============================================================
    $galerie = [];
    if (!empty($rows)) {
        $ids = array_column($rows, 'id');
        $in  = implode(',', array_fill(0, count($ids), '?'));
        $imgStmt = $dbh->prepare("
            SELECT artikel_id, bild_url, bild_typ, bild_reihenfolge, farbe_schluessel
            FROM artikel_bilder
            WHERE artikel_id IN ($in)
            ORDER BY artikel_id, bild_reihenfolge ASC, id ASC
        ");
        $imgStmt->execute($ids);
        foreach ($imgStmt->fetchAll() as $img) {
            $url = bildMitExtension($img['bild_url']);
            if ($url) {
                $galerie[$img['artikel_id']][] = [
                    'url'             => $url,
                    'typ'             => $img['bild_typ'],
                    'farbe_schluessel'=> $img['farbe_schluessel'],
                ];
            }
        }
    }

    // ============================================================
    // CONSTRUCTION DES PRODUITS
    // ============================================================
    $products = [];

    foreach ($rows as $row) {
        // --- JSON ---
        $specs = !empty($row['spezifikationen'])
            ? json_decode($row['spezifikationen'], true)
            : null;
        $varianten = !empty($row['varianten'])
            ? json_decode($row['varianten'], true)
            : [];

        // --- Prix ---
        $preisNetto  = (float)$row['preis'];
        $preisBrutto = round($preisNetto * 1.19, 2);

        $angebotspreisNetto  = $row['angebotspreis'] !== null ? (float)$row['angebotspreis'] : null;
        $angebotspreisBrutto = $angebotspreisNetto !== null
            ? round($angebotspreisNetto * 1.19, 2)
            : null;

        $gebrauchtPreisNetto  = null;
        $gebrauchtPreisBrutto = null;
        $ersparnis            = 0;

        if ((bool)$row['ist_gebraucht'] && $row['gebraucht_preis'] !== null) {
            $gebrauchtPreisNetto  = (float)$row['gebraucht_preis'];
            $gebrauchtPreisBrutto = round($gebrauchtPreisNetto * 1.19, 2);
            $ersparnis            = round($preisNetto - $gebrauchtPreisNetto, 2);
        }

        // --- Image principale ---
        $hauptbild = !empty($row['hauptbild'])
            ? bildMitExtension($row['hauptbild'])
            : null;

        // --- Features (à partir des colonnes spez_* et des JSON) ---
        $features = [];

        if (!empty($row['spez_display_typ']))       $features[] = "Display: {$row['spez_display_typ']}";
        if (!empty($row['spez_widerstandstyp']))    $features[] = "Widerstand: {$row['spez_widerstandstyp']}";
        if ($row['spez_hat_ekg']       !== null)    $features[] = "EKG: "         . ((bool)$row['spez_hat_ekg']       ? 'Ja' : 'Nein');
        if ($row['spez_hat_bluetooth'] !== null)    $features[] = "Bluetooth: "   . ((bool)$row['spez_hat_bluetooth'] ? 'Ja' : 'Nein');
        if ($row['spez_hat_wifi']      !== null)    $features[] = "WLAN: "        . ((bool)$row['spez_hat_wifi']      ? 'Ja' : 'Nein');
        if ($row['spez_hat_touchscreen'] !== null)  $features[] = "Touchscreen: " . ((bool)$row['spez_hat_touchscreen'] ? 'Ja' : 'Nein');
        if ($row['spez_ist_klappbar']  !== null)    $features[] = "Klappbar: "    . ((bool)$row['spez_ist_klappbar']  ? 'Ja' : 'Nein');
        if ($row['spez_hat_rueckenlehne'] !== null) $features[] = "Rückenlehne: " . ((bool)$row['spez_hat_rueckenlehne'] ? 'Ja' : 'Nein');

        if (!empty($row['belastbarkeit']))   $features[] = "Max. Benutzergewicht: {$row['belastbarkeit']} kg";
        if (!empty($row['garantie_jahre']))  $features[] = "Garantie: {$row['garantie_jahre']} Jahre";
        if (!empty($row['stromversorgung'])) $features[] = "Stromversorgung: {$row['stromversorgung']}";
        if (!empty($row['anwendungsbereich'])) $features[] = "Anwendungsbereich: {$row['anwendungsbereich']}";

        // Ajouter les specs JSON si présentes
        if (is_array($specs)) {
            foreach ($specs as $key => $value) {
                if (is_scalar($value) && $value !== '') {
                    $features[] = "$key: $value";
                }
            }
        }

        // --- Construction de l'objet produit ---
        $products[] = [
            'id'                     => (int)$row['id'],
            'artikelnummer'          => $row['artikelnummer'],
            'name'                   => $row['bezeichnung'],
            'slug'                   => $row['slug'],
            'brand'                  => $row['marke'],
            'category'               => $row['kategorie_schluessel'],
            'subcategory'            => $row['unterkategorie_schluessel'],
            'artikeltyp'             => $row['artikeltyp'],

            // Prix
            'price'                  => $preisNetto,
            'priceInclVat'           => $preisBrutto,
            'offerPrice'             => $angebotspreisNetto,
            'offerPriceInclVat'      => $angebotspreisBrutto,
            'currency'               => $row['waehrung'] ?: 'EUR',

            // Image
            'main_image'             => $hauptbild,
            'images'                 => $galerie[$row['id']] ?? [],

            // Statuts
            'inStock'                => (bool)$row['auf_lager'],
            'isNew'                  => (bool)$row['ist_neu'],
            'bestSeller'             => (bool)$row['bestseller'],
            'isOffer'                => (bool)$row['ist_angebot'],
            'isUsed'                 => (bool)$row['ist_gebraucht'],
            'condition'              => $row['zustand'],

            // Prix gebraucht
            'usedPriceNet'           => $gebrauchtPreisNetto,
            'usedPriceInclVat'       => $gebrauchtPreisBrutto,
            'savings'                => $ersparnis,
            'usedDescription'        => $row['gebraucht_beschreibung'],

            // Caractéristiques
            'features'               => $features,
            'specifications'         => $specs,
            'variants'               => $varianten,

            // Standard-Variante
            'defaultColor'           => $row['variante_standard_farbe'],
            'defaultVariantNumber'   => $row['variante_standard_artikelnummer'],

            // Meta
            'warranty_years'         => $row['garantie_jahre'] !== null ? (int)$row['garantie_jahre'] : null,
            'weight_capacity'        => $row['belastbarkeit'] !== null ? (int)$row['belastbarkeit'] : null,
            'description'            => $row['beschreibung'],
        ];
    }

    echo json_encode([
        'success'  => true,
        'products' => $products,
        'count'    => count($products),
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Database error: ' . $e->getMessage(),
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Server error: ' . $e->getMessage(),
    ]);
}