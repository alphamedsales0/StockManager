<?php
// kunden_create.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/database_connect.php';

function generateKundennummer($pdo) {
    $prefix = 'KD-';
    $date = date('Ymd');
    $stmt = $pdo->prepare("SELECT MAX(CAST(SUBSTRING(kundennummer, 12) AS UNSIGNED)) FROM kunden WHERE kundennummer LIKE ?");
    $stmt->execute([$prefix . $date . '-%']);
    $max = $stmt->fetchColumn();
    $next = ($max ? $max + 1 : 1);
    return $prefix . $date . '-' . str_pad($next, 4, '0', STR_PAD_LEFT);
}

function executeWithCheck($pdo, $sql, $params = []) {
    $pc = substr_count($sql, '?');
    $cc = count($params);
    if ($pc !== $cc) throw new Exception("SQL Placeholder Fehler: {$pc} vs {$cc}");
    $stmt = $pdo->prepare($sql);
    if (!$stmt->execute($params)) throw new Exception("SQL-Fehler: " . implode(' ', $stmt->errorInfo()));
    return $stmt;
}

$input = [];
if (!empty($_POST['kunde'])) {
    $input = json_decode($_POST['kunde'], true) ?? [];
} else {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true) ?? [];
}

file_put_contents(__DIR__ . '/debug.log', date('Y-m-d H:i:s') . " KUNDEN CREATE INPUT: " . json_encode($input) . "\n", FILE_APPEND);

$personType = $input['person_type'] ?? 'Privatkunde';

if ($personType === 'Privatkunde') {
    if (empty($input['vorname']) || empty($input['nachname'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Vorname und Nachname sind erforderlich']);
        exit;
    }
} else {
    if (empty($input['firma'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Firmenname ist erforderlich']);
        exit;
    }
}

if (empty($input['email'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'E-Mail ist erforderlich']);
    exit;
}

$pdo->beginTransaction();

try {
    $kundennummer = generateKundennummer($pdo);

    // 1. kunden
    executeWithCheck($pdo,
        "INSERT INTO kunden (kundennummer, person_type, profilbild, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())",
        [$kundennummer, $personType, $input['profilbild'] ?? null]);
    $kundeId = $pdo->lastInsertId();

    // 2. Personendaten
    if ($personType === 'Privatkunde') {
        executeWithCheck($pdo,
            "INSERT INTO kunden_personendaten (kunde_id, anrede, vorname, nachname) VALUES (?, ?, ?, ?)",
            [$kundeId, $input['anrede'] ?? null, $input['vorname'], $input['nachname']]);
    }

    // 3. Gewerbedaten
    if ($personType === 'Gewerbekunde') {
        executeWithCheck($pdo,
            "INSERT INTO kunden_gewerbedaten (kunde_id, firma, zusatz, kontakt, hrb_register_nummer, hrb_gerichtsstand) VALUES (?, ?, ?, ?, ?, ?)",
            [$kundeId, $input['firma'], $input['zusatz'] ?? null, $input['kontakt'] ?? null, $input['hrb_register_nummer'] ?? null, $input['hrb_gerichtsstand'] ?? null]);
    }

    // 4. Authentifizierung
    $passwort = $input['passwort'] ?? bin2hex(random_bytes(8));
    executeWithCheck($pdo,
        "INSERT INTO kunden_authentifizierung (kunde_id, email, passwort_hash, email_verifiziert, agb_accepted, datenschutz_accepted) VALUES (?, ?, ?, ?, ?, ?)",
        [$kundeId, $input['email'], password_hash($passwort, PASSWORD_DEFAULT),
         !empty($input['email_verifiziert']) ? 1 : 0,
         !empty($input['agb_accepted']) ? 1 : 0,
         !empty($input['datenschutz_accepted']) ? 1 : 0]);

    // 5. Identifikation
    executeWithCheck($pdo,
        "INSERT INTO kunden_identifikation (kunde_id, steuernummer, ust_id_nr, auslandskunden, eg_ausland) VALUES (?, ?, ?, ?, ?)",
        [$kundeId, $input['steuernummer'] ?? null, $input['ust_id_nr'] ?? null,
         !empty($input['auslandskunden']) ? 1 : 0, !empty($input['eg_ausland']) ? 1 : 0]);

    // 6. Klassifizierung
    executeWithCheck($pdo,
        "INSERT INTO kunden_klassifizierung (kunde_id, kundengruppe, branche) VALUES (?, ?, ?)",
        [$kundeId, $input['kundengruppe'] ?? null, $input['branche'] ?? null]);

    // 7. Adressen
    if (!empty($input['adressen']) && is_array($input['adressen'])) {
        foreach ($input['adressen'] as $addr) {
            if (empty($addr['strasse']) && empty($addr['ort'])) continue;
            executeWithCheck($pdo,
                "INSERT INTO kunden_adressen (kunde_id, typ, strasse, hausnummer, adresszusatz, plz, ort, bundesland, land, postfach, ist_standard) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [$kundeId, $addr['typ'] ?? 'Rechnungsadresse', $addr['strasse'] ?? null, $addr['hausnummer'] ?? null,
                 $addr['adresszusatz'] ?? null, $addr['plz'] ?? null, $addr['ort'] ?? null,
                 $addr['bundesland'] ?? null, $addr['land'] ?? 'Deutschland', $addr['postfach'] ?? null,
                 !empty($addr['ist_standard']) ? 1 : 0]);
        }
    }

    // 8. Kommunikation
    if (!empty($input['kommunikation']) && is_array($input['kommunikation'])) {
        foreach ($input['kommunikation'] as $k) {
            if (empty($k['wert'])) continue;
            executeWithCheck($pdo,
                "INSERT INTO kunden_kommunikation (kunde_id, typ, wert, bemerkung, ist_standard) VALUES (?, ?, ?, ?, ?)",
                [$kundeId, $k['typ'] ?? 'Telefon', $k['wert'], $k['bemerkung'] ?? null, !empty($k['ist_standard']) ? 1 : 0]);
        }
    }

    // 9. Bankverbindungen
    if (!empty($input['bankverbindungen']) && is_array($input['bankverbindungen'])) {
        foreach ($input['bankverbindungen'] as $b) {
            if (empty($b['iban'])) continue;
            executeWithCheck($pdo,
                "INSERT INTO kunden_bankverbindung (kunde_id, kontoinhaber, iban, bic, bankname, ist_standard) VALUES (?, ?, ?, ?, ?, ?)",
                [$kundeId, $b['kontoinhaber'] ?? null, preg_replace('/\s+/', '', strtoupper($b['iban'])),
                 $b['bic'] ?? null, $b['bankname'] ?? null, !empty($b['ist_standard']) ? 1 : 0]);
        }
    }

    // 10. Rechnungsdaten
    executeWithCheck($pdo,
        "INSERT INTO kunden_rechnungsdaten (kunde_id, zahlungsweise, versandart, zahlungsziel, gesamtrabatt) VALUES (?, ?, ?, ?, ?)",
        [$kundeId, $input['zahlungsweise'] ?? null, $input['versandart'] ?? null,
         !empty($input['zahlungsziel']) ? $input['zahlungsziel'] : null,
         !empty($input['gesamtrabatt']) ? $input['gesamtrabatt'] : null]);

    // 11. Preisgruppen
    if (!empty($input['preisgruppen']) && is_array($input['preisgruppen'])) {
        foreach ($input['preisgruppen'] as $p) {
            if (empty($p['preisgruppe'])) continue;
            executeWithCheck($pdo,
                "INSERT INTO kunden_preisgruppen (kunde_id, preisgruppe, prioritaet) VALUES (?, ?, ?)",
                [$kundeId, $p['preisgruppe'], $p['prioritaet'] ?? 0]);
        }
    }

    // 12. Artikelrabatte
    if (!empty($input['artikelrabatte']) && is_array($input['artikelrabatte'])) {
        foreach ($input['artikelrabatte'] as $a) {
            if (empty($a['prozent'])) continue;
            executeWithCheck($pdo,
                "INSERT INTO kunden_artikelrabatte (kunde_id, stufe, prozent) VALUES (?, ?, ?)",
                [$kundeId, $a['stufe'] ?? 1, $a['prozent']]);
        }
    }

    // 13. Mahngebühren
    if (!empty($input['mahngebuehren']) && is_array($input['mahngebuehren'])) {
        foreach ($input['mahngebuehren'] as $m) {
            if (empty($m['betrag'])) continue;
            executeWithCheck($pdo,
                "INSERT INTO kunden_mahngebuehren (kunde_id, stufe, betrag) VALUES (?, ?, ?)",
                [$kundeId, $m['stufe'] ?? 1, $m['betrag']]);
        }
    }

    // 14. Skonto
    if (!empty($input['skonto']) && is_array($input['skonto'])) {
        foreach ($input['skonto'] as $s) {
            if (empty($s['prozent']) || empty($s['tage'])) continue;
            executeWithCheck($pdo,
                "INSERT INTO kunden_skonto (kunde_id, stufe, prozent, tage) VALUES (?, ?, ?, ?)",
                [$kundeId, $s['stufe'] ?? 1, $s['prozent'], $s['tage']]);
        }
    }

    // 15. Bemerkungen
    if (!empty($input['bemerkungen']) && is_array($input['bemerkungen'])) {
        foreach ($input['bemerkungen'] as $b) {
            if (empty($b['bemerkung'])) continue;
            executeWithCheck($pdo,
                "INSERT INTO kunden_bemerkungen (kunde_id, typ, bemerkung, erstellt_am) VALUES (?, ?, ?, NOW())",
                [$kundeId, $b['typ'] ?? 'internal', $b['bemerkung']]);
        }
    }

    // 16. Zusatzfelder
    if (!empty($input['zusatzfelder']) && is_array($input['zusatzfelder'])) {
        foreach ($input['zusatzfelder'] as $z) {
            if (empty($z['feldname'])) continue;
            executeWithCheck($pdo,
                "INSERT INTO kunden_zusatzfelder (kunde_id, feldname, wert) VALUES (?, ?, ?)",
                [$kundeId, $z['feldname'], $z['wert'] ?? null]);
        }
    }

    // 17. Photo upload
    $profilbildPath = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['photo'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!isset($allowed[$mime])) throw new Exception('Ungültiges Bildformat.');
        if ($file['size'] > 5 * 1024 * 1024) throw new Exception('Datei zu groß (max. 5 MB).');

        $ext = $allowed[$mime];
        $docRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
        $uploadsDir = $docRoot . '/uploads/';
        $kundenDir = $uploadsDir . 'kunden/';
        $kundeDir = $kundenDir . $kundeId . '/';

        foreach ([$uploadsDir, $kundenDir, $kundeDir] as $dir) {
            if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
                throw new Exception("Verzeichnis konnte nicht erstellt werden: {$dir}");
            }
        }

        $photoName = 'profil_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $targetPath = $kundeDir . $photoName;
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new Exception('Fehler beim Speichern des Fotos.');
        }

        $profilbildPath = '/uploads/kunden/' . $kundeId . '/' . $photoName;
        executeWithCheck($pdo, "UPDATE kunden SET profilbild = ? WHERE id = ?", [$profilbildPath, $kundeId]);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Kunde erfolgreich angelegt',
        'kunde_id' => $kundeId,
        'kundennummer' => $kundennummer,
        'data' => ['profilbild' => $profilbildPath]
    ]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    file_put_contents(__DIR__ . '/debug.log', date('Y-m-d H:i:s') . " KUNDEN CREATE ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}