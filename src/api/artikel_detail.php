<?php
// api/artikel_detail.php — Détail d'un article avec images, couleurs et livraison
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
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
    // PARAMÈTRES
    // ============================================================
    $id            = isset($_GET['id']) ? (int)$_GET['id'] : null;
    $slug          = isset($_GET['slug']) ? trim($_GET['slug']) : null;
    $artikelnummer = isset($_GET['artikelnummer']) ? trim($_GET['artikelnummer']) : null;

    if (!$id && !$slug && !$artikelnummer) {
        echo json_encode(['success' => false, 'error' => 'id, slug oder artikelnummer erforderlich']);
        exit;
    }

    // ============================================================
    // ARTICLE
    // ============================================================
    $sql = "SELECT * FROM artikel WHERE ";
    $params = [];

    if ($id) {
        $sql .= "id = :id";
        $params[':id'] = $id;
    } elseif ($slug) {
        $sql .= "slug = :slug";
        $params[':slug'] = $slug;
    } else {
        $sql .= "artikelnummer = :artikelnummer";
        $params[':artikelnummer'] = $artikelnummer;
    }

    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
    $artikel = $stmt->fetch();

    if (!$artikel) {
        echo json_encode(['success' => false, 'error' => 'Artikel nicht gefunden']);
        exit;
    }

    // ============================================================
    // PARSING JSON
    // ============================================================
    $artikel['spezifikationen'] = !empty($artikel['spezifikationen'])
        ? json_decode($artikel['spezifikationen'], true)
        : null;

    $artikel['varianten'] = !empty($artikel['varianten'])
        ? json_decode($artikel['varianten'], true)
        : [];

    // ============================================================
    // BOOLÉENS
    // ============================================================
    $artikel['auf_lager']     = (bool)$artikel['auf_lager'];
    $artikel['ist_neu']       = (bool)$artikel['ist_neu'];
    $artikel['ist_angebot']   = (bool)$artikel['ist_angebot'];
    $artikel['bestseller']    = (bool)$artikel['bestseller'];
    $artikel['ist_gebraucht'] = (bool)$artikel['ist_gebraucht'];

    // ============================================================
    // PRIX
    // ============================================================
    $artikel['preis_inkl_mwst'] = round((float)$artikel['preis'] * 1.19, 2);

    if ($artikel['ist_gebraucht'] && $artikel['gebraucht_preis'] !== null) {
        $artikel['gebraucht_preis']        = (float)$artikel['gebraucht_preis'];
        $artikel['gebraucht_preis_brutto'] = round((float)$artikel['gebraucht_preis'] * 1.19, 2);
        $artikel['ersparnis']              = round(
            (float)$artikel['preis'] - (float)$artikel['gebraucht_preis'],
            2
        );
    } else {
        $artikel['gebraucht_preis']        = null;
        $artikel['gebraucht_preis_brutto'] = null;
        $artikel['ersparnis']              = 0;
    }

    // ============================================================
    // IMAGE PRINCIPALE
    // ============================================================
    if (!empty($artikel['hauptbild'])) {
        $artikel['hauptbild'] = bildMitExtension($artikel['hauptbild']);
    }

    // ============================================================
    // COULEURS
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
    $farbenStmt->execute([$artikel['id']]);
    $farben = $farbenStmt->fetchAll();

    $artikel['farben'] = array_map(function($f) use ($artikel) {
        $bildUrl = null;
        if (!empty($f['farbe_bild'])) {
            $bildUrl = bildMitExtension($f['farbe_bild']);
        }

        return [
            'id'              => (int)$f['id'],
            'schluessel'      => $f['schluessel'],
            'bezeichnung'     => $f['bezeichnung'],
            'hex_code'        => $f['hex_code'],
            'ist_standard'    => (bool)$f['ist_standard'],
            'artikelnummer'   => $f['artikelnummer_variante'] ?: $artikel['artikelnummer'],
            'preis'           => $f['farbe_preis'] !== null
                ? (float)$f['farbe_preis']
                : (float)$artikel['preis'],
            'preis_inkl_mwst' => $f['farbe_preis'] !== null
                ? round((float)$f['farbe_preis'] * 1.19, 2)
                : round((float)$artikel['preis'] * 1.19, 2),
            'bild_url'        => $bildUrl
        ];
    }, $farben);

    // ============================================================
    // ✅ IMAGES DE GALERIE — AVEC farbe_schluessel (CORRIGÉ)
    // ============================================================
    $imgStmt = $dbh->prepare("
        SELECT bild_url, bild_typ, bild_reihenfolge, farbe_schluessel
        FROM artikel_bilder
        WHERE artikel_id = ?
        ORDER BY bild_reihenfolge ASC, id ASC
    ");
    $imgStmt->execute([$artikel['id']]);
    $bilder = $imgStmt->fetchAll();

    foreach ($bilder as &$bild) {
        if (!empty($bild['bild_url'])) {
            $bild['bild_url'] = bildMitExtension($bild['bild_url']);
        }
    }
    unset($bild);

    $artikel['bilder'] = $bilder;

    // ============================================================
    // LIVRAISON
    // ============================================================
    $shipStmt = $dbh->prepare("
        SELECT versandkosten, kostenlos_ab, versandart, lieferzeit_tage
        FROM versandregeln
        WHERE artikel_id = ?
        LIMIT 1
    ");
    $shipStmt->execute([$artikel['id']]);
    $versand = $shipStmt->fetch();

    if ($versand) {
        $artikel['versandkosten']   = (float)$versand['versandkosten'];
        $artikel['kostenlos_ab']    = (float)$versand['kostenlos_ab'];
        $artikel['versandart']      = $versand['versandart'];
        $artikel['lieferzeit_tage'] = (int)$versand['lieferzeit_tage'];
    } else {
        $artikel['versandkosten']   = 180.00;
        $artikel['kostenlos_ab']    = 50.00;
        $artikel['versandart']      = 'Spedition';
        $artikel['lieferzeit_tage'] = 7;
    }

    // ============================================================
    // CATÉGORIE
    // ============================================================
    $katStmt = $dbh->prepare("
        SELECT bezeichnung, route FROM kategorien WHERE schluessel = ? LIMIT 1
    ");
    $katStmt->execute([$artikel['kategorie_schluessel']]);
    $kat = $katStmt->fetch();

    $artikel['kategorie_bezeichnung'] = $kat['bezeichnung'] ?? '';
    $artikel['kategorie_route']       = $kat['route'] ?? '';

    // ============================================================
    // MARQUE
    // ============================================================
    $markeStmt = $dbh->prepare("
        SELECT name, logo_url, webseite FROM marken
        WHERE schluessel = ? OR name = ?
        LIMIT 1
    ");
    $markeStmt->execute([
        strtolower(str_replace(' ', '-', $artikel['marke'])),
        $artikel['marke']
    ]);
    $markeInfo = $markeStmt->fetch();

    if ($markeInfo) {
        $artikel['marke_name']     = $markeInfo['name'];
        $artikel['marke_logo']     = $markeInfo['logo_url'];
        $artikel['marke_webseite'] = $markeInfo['webseite'];
    } else {
        $artikel['marke_name']     = $artikel['marke'];
        $artikel['marke_logo']     = null;
        $artikel['marke_webseite'] = null;
    }

    // ============================================================
    // ARTICLES SIMILAIRES
    // ============================================================
    $aehnlichStmt = $dbh->prepare("
        SELECT id, artikelnummer, bezeichnung, slug, marke, preis, hauptbild
        FROM artikel
        WHERE kategorie_schluessel = ?
          AND artikeltyp = ?
          AND id != ?
          AND auf_lager = 1
        ORDER BY bestseller DESC, ist_neu DESC
        LIMIT 4
    ");
    $aehnlichStmt->execute([
        $artikel['kategorie_schluessel'],
        $artikel['artikeltyp'],
        $artikel['id']
    ]);
    $aehnliche = $aehnlichStmt->fetchAll();

    foreach ($aehnliche as &$a) {
        if (!empty($a['hauptbild'])) {
            $a['hauptbild'] = bildMitExtension($a['hauptbild']);
        }
    }
    unset($a);

    $artikel['aehnliche_artikel'] = $aehnliche;

    // ============================================================
    // RÉPONSE
    // ============================================================
    echo json_encode([
        'success' => true,
        'artikel' => $artikel
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Datenbankfehler: ' . $e->getMessage()
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Serverfehler: ' . $e->getMessage()
    ]);
}