<?php
// api/artikel.php — Liste des articles avec filtres
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/bild_helper.php';   // ← AJOUTER CETTE LIGNE

try {
    // ============================================================
    // PARAMÈTRES
    // ============================================================
    $kategorie      = $_GET['kategorie']      ?? '';
    $unterkategorie = $_GET['unterkategorie'] ?? '';
    $artikeltyp     = $_GET['artikeltyp']     ?? '';
    $marke          = $_GET['marke']          ?? '';
    $suche          = $_GET['suche']          ?? '';
    $minPreis       = isset($_GET['minPreis']) ? (float)$_GET['minPreis'] : 0;
    $maxPreis       = isset($_GET['maxPreis']) ? (float)$_GET['maxPreis'] : 25000;
    $nurNeu         = isset($_GET['nurNeu'])   && $_GET['nurNeu']   == '1';
    $nurAngebot     = isset($_GET['nurAngebot']) && $_GET['nurAngebot'] == '1';
    $nurGebraucht   = isset($_GET['gebraucht']) && $_GET['gebraucht'] == '1';
    $zustand        = $_GET['zustand']        ?? '';
    $limit          = isset($_GET['limit'])  ? (int)$_GET['limit']  : 100;
    $offset         = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

    // ============================================================
    // REQUÊTE
    // ============================================================
    $sql = "SELECT id, artikelnummer, bezeichnung, slug, marke,
                   kategorie_schluessel, unterkategorie_schluessel, artikeltyp,
                   preis, angebotspreis, waehrung, hauptbild, auf_lager,
                   ist_neu, ist_angebot, bestseller, garantie_jahre,
                   belastbarkeit, stromversorgung, anwendungsbereich, beschreibung,
                   spezifikationen, varianten,
                   spez_display_typ, spez_widerstandstyp, spez_hat_ekg,
                   spez_hat_bluetooth, spez_hat_wifi, spez_hat_touchscreen,
                   spez_ist_klappbar, spez_hat_rueckenlehne,
                   variante_standard_farbe, variante_standard_artikelnummer,
                   ist_gebraucht, zustand, gebraucht_preis, gebraucht_beschreibung
            FROM artikel
            WHERE preis BETWEEN :minPreis AND :maxPreis AND auf_lager = 1";

    $params = [':minPreis' => $minPreis, ':maxPreis' => $maxPreis];

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
        $sql .= " AND (bezeichnung LIKE :suche OR marke LIKE :suche OR artikelnummer LIKE :suche OR beschreibung LIKE :suche)";
        $params[':suche'] = "%$suche%";
    }
    if ($nurNeu)     { $sql .= " AND ist_neu = 1"; }
    if ($nurAngebot) { $sql .= " AND ist_angebot = 1"; }
    if ($nurGebraucht) { $sql .= " AND ist_gebraucht = 1"; }
    if ($zustand !== '') {
        $sql .= " AND zustand = :zustand";
        $params[':zustand'] = $zustand;
    }

    $sql .= " ORDER BY ist_gebraucht ASC, bestseller DESC, ist_neu DESC, bezeichnung ASC LIMIT $limit OFFSET $offset";

    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    // ============================================================
    // PARSING JSON + CALCULS
    // ============================================================
    foreach ($rows as &$row) {
        $row['spezifikationen'] = $row['spezifikationen'] ? json_decode($row['spezifikationen'], true) : null;
        $row['varianten']       = $row['varianten'] ? json_decode($row['varianten'], true) : [];
        $row['auf_lager']       = (bool)$row['auf_lager'];
        $row['ist_neu']         = (bool)$row['ist_neu'];
        $row['ist_angebot']     = (bool)$row['ist_angebot'];
        $row['bestseller']      = (bool)$row['bestseller'];
        $row['ist_gebraucht']   = (bool)$row['ist_gebraucht'];
        $row['preis_inkl_mwst'] = round((float)$row['preis'] * 1.19, 2);

        // 🎯 Résoudre l'extension de l'image principale
        if (!empty($row['hauptbild'])) {
            $row['hauptbild'] = bildMitExtension($row['hauptbild']);
        }

        // Prix gebraucht
        if ($row['ist_gebraucht'] && $row['gebraucht_preis'] !== null) {
            $row['preis_gebraucht_netto']  = (float)$row['gebraucht_preis'];
            $row['preis_gebraucht_brutto'] = round((float)$row['gebraucht_preis'] * 1.19, 2);
        } else {
            $row['preis_gebraucht_netto']  = null;
            $row['preis_gebraucht_brutto'] = null;
        }
    }
    unset($row);

    // ============================================================
    // IMAGES DE GALERIE
    // ============================================================
    if (!empty($rows)) {
        $ids = array_column($rows, 'id');
        $in  = implode(',', array_fill(0, count($ids), '?'));
        $imgStmt = $dbh->prepare("SELECT artikel_id, bild_url FROM artikel_bilder WHERE artikel_id IN ($in) ORDER BY bild_reihenfolge");
        $imgStmt->execute($ids);
        $images = [];
        foreach ($imgStmt->fetchAll() as $img) {
            // Résoudre l'extension
            $resolvedUrl = bildMitExtension($img['bild_url']);
            if ($resolvedUrl) {
                $images[$img['artikel_id']][] = $resolvedUrl;
            }
        }
        foreach ($rows as &$row) {
            $row['bilder'] = $images[$row['id']] ?? [];
        }
        unset($row);
    }

    // ============================================================
    // RÉPONSE
    // ============================================================
    echo json_encode([
        'success' => true,
        'artikel' => $rows,
        'anzahl'  => count($rows),
        'filter'  => [
            'gebraucht' => $nurGebraucht,
            'zustand'   => $zustand
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}