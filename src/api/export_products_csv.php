<?php
// export_products_csv.php
// Export CSV serveur de tous les produits (compatible Excel DE)
// Aligné avec la structure de get_all_products.php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/database_connect.php';
require_once __DIR__ . '/bild_helper.php';

$dbh = $pdo;

// ============================================================
// FILTRES OPTIONNELS (identiques à get_all_products.php)
// ============================================================
$kategorie      = $_GET['kategorie']      ?? '';
$unterkategorie = $_GET['unterkategorie'] ?? '';
$artikeltyp     = $_GET['artikeltyp']     ?? '';
$marke          = $_GET['marke']          ?? '';
$suche          = $_GET['suche']          ?? '';

// ============================================================
// REQUÊTE
// ============================================================
$sql = "SELECT
            id, artikelnummer, bezeichnung, slug, marke,
            kategorie_schluessel, unterkategorie_schluessel, artikeltyp,
            preis, angebotspreis, waehrung, hauptbild,
            auf_lager, ist_neu, ist_angebot, bestseller,
            garantie_jahre, belastbarkeit, stromversorgung, anwendungsbereich,
            beschreibung, spezifikationen, varianten,
            ist_gebraucht, zustand, gebraucht_preis, gebraucht_beschreibung,
            variante_standard_farbe, variante_standard_artikelnummer
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

$sql .= " ORDER BY ist_gebraucht ASC, bestseller DESC, ist_neu DESC, bezeichnung ASC";

try {
    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error'   => 'Database error: ' . $e->getMessage()
    ]);
    exit;
}

// ============================================================
// NOM DU FICHIER
// ============================================================
$suffix = 'alle';
if ($kategorie !== '' && $kategorie !== 'alle' && $kategorie !== 'all') $suffix = $kategorie;
elseif ($suche !== '') $suffix = 'suche';
$filename = 'produkte_' . preg_replace('/[^a-z0-9_\-]/i', '_', $suffix) . '_' . date('Y-m-d') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// ============================================================
// COLONNES FIXES
// ============================================================
$columns = [
    'id'                                => 'ID',
    'artikelnummer'                     => 'Artikelnummer',
    'bezeichnung'                       => 'Bezeichnung',
    'slug'                              => 'Slug',
    'marke'                             => 'Marke',
    'kategorie_schluessel'              => 'Kategorie',
    'unterkategorie_schluessel'         => 'Unterkategorie',
    'artikeltyp'                        => 'Artikeltyp',
    'preis'                             => 'Preis (Netto)',
    'angebotspreis'                     => 'Angebotspreis (Netto)',
    'waehrung'                          => 'Währung',
    'auf_lager'                         => 'Auf Lager',
    'ist_neu'                           => 'Neu',
    'ist_angebot'                       => 'Angebot',
    'bestseller'                        => 'Bestseller',
    'ist_gebraucht'                     => 'Gebraucht',
    'zustand'                           => 'Zustand',
    'gebraucht_preis'                   => 'Gebrauchtpreis (Netto)',
    'gebraucht_beschreibung'            => 'Gebraucht-Beschreibung',
    'garantie_jahre'                    => 'Garantie (Jahre)',
    'belastbarkeit'                     => 'Tragfähigkeit (kg)',
    'stromversorgung'                   => 'Stromversorgung',
    'anwendungsbereich'                 => 'Anwendungsbereich',
    'beschreibung'                      => 'Beschreibung',
    'variante_standard_farbe'           => 'Standardfarbe',
    'variante_standard_artikelnummer'   => 'Standard-Art.-Nr.',
    'hauptbild'                         => 'Hauptbild-URL',
];

// ============================================================
// DÉTECTION DYNAMIQUE DES SPÉCIFICATIONS
// ============================================================
$allSpecKeys = [];
foreach ($rows as $row) {
    if (!empty($row['spezifikationen'])) {
        $specs = json_decode($row['spezifikationen'], true);
        if (is_array($specs)) {
            foreach (array_keys($specs) as $k) {
                $allSpecKeys[$k] = true;
            }
        }
    }
}
$allSpecKeys = array_keys($allSpecKeys);
sort($allSpecKeys);

// ============================================================
// ÉCHAPPEMENT CSV (✅ CORRIGÉ)
// ============================================================
function csvEscape($val) {
    if ($val === null) return '';                       // ✅ PHP ne connaît pas "undefined"
    if (is_bool($val)) return $val ? 'Ja' : 'Nein';
    $s = (string)$val;
    $s = str_replace(["\r\n", "\r", "\n"], ' ', $s);
    if (strpos($s, ';') !== false || strpos($s, '"') !== false) {
        return '"' . str_replace('"', '""', $s) . '"';
    }
    return $s;
}

// ============================================================
// BOM UTF-8 (Excel)
// ============================================================
echo "\xEF\xBB\xBF";

// ============================================================
// EN-TÊTE
// ============================================================
$header = array_values($columns);
foreach ($allSpecKeys as $key) {
    $header[] = 'Spez: ' . $key;
}

echo implode(';', array_map('csvEscape', $header)) . "\r\n";

// ============================================================
// LIGNES
// ============================================================
foreach ($rows as $row) {
    $line = [];

    foreach ($columns as $col => $label) {
        $val = $row[$col] ?? '';

        // Booléens → Ja / Nein
        if (in_array($col, ['auf_lager', 'ist_neu', 'ist_angebot', 'bestseller', 'ist_gebraucht'], true)) {
            $val = $val ? 'Ja' : 'Nein';
        }

        // Hauptbild → URL complète
        if ($col === 'hauptbild' && !empty($val)) {
            $val = function_exists('bildMitExtension') ? (bildMitExtension($val) ?: $val) : $val;
        }

        $line[] = csvEscape($val);
    }

    // Spécifications dynamiques
    $specs = !empty($row['spezifikationen'])
        ? json_decode($row['spezifikationen'], true)
        : [];

    foreach ($allSpecKeys as $key) {
        $v = $specs[$key] ?? '';
        $line[] = csvEscape($v);
    }

    echo implode(';', $line) . "\r\n";
}

exit;