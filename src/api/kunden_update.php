<?php
// kunden_update.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: PUT, POST, OPTIONS");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/database_connect.php';

$id = $_GET['id'] ?? 0;
if (!$id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID fehlt']);
    exit;
}

$input = [];
if (!empty($_POST['kunde'])) {
    $input = json_decode($_POST['kunde'], true) ?? [];
} else {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true) ?? [];
}

file_put_contents(__DIR__ . '/debug.log', date('Y-m-d H:i:s') . " UPDATE [{$id}]: " . json_encode($input) . "\n", FILE_APPEND);

$pdo->beginTransaction();

try {
    $stmt = $pdo->prepare("SELECT id FROM kunden WHERE id = ?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) throw new Exception('Kunde nicht gefunden');

    $personType = $input['person_type'] ?? 'Privatkunde';

    // 1. kunden
    $stmt = $pdo->prepare("UPDATE kunden SET person_type = ?, profilbild = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$personType, $input['profilbild'] ?? null, $id]);

    // 2. Personendaten / Gewerbedaten
    if ($personType === 'Privatkunde') {
        $stmt = $pdo->prepare("DELETE FROM kunden_gewerbedaten WHERE kunde_id = ?");
        $stmt->execute([$id]);

        $stmt = $pdo->prepare("DELETE FROM kunden_personendaten WHERE kunde_id = ?");
        $stmt->execute([$id]);
        $stmt = $pdo->prepare("INSERT INTO kunden_personendaten (kunde_id, anrede, vorname, nachname) VALUES (?, ?, ?, ?)");
        $stmt->execute([$id, $input['anrede'] ?? null, $input['vorname'] ?? '', $input['nachname'] ?? '']);
    } else {
        $stmt = $pdo->prepare("DELETE FROM kunden_personendaten WHERE kunde_id = ?");
        $stmt->execute([$id]);

        $stmt = $pdo->prepare("DELETE FROM kunden_gewerbedaten WHERE kunde_id = ?");
        $stmt->execute([$id]);
        $stmt = $pdo->prepare("INSERT INTO kunden_gewerbedaten (kunde_id, firma, zusatz, kontakt, hrb_register_nummer, hrb_gerichtsstand) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$id, $input['firma'] ?? '', $input['zusatz'] ?? null, $input['kontakt'] ?? null, $input['hrb_register_nummer'] ?? null, $input['hrb_gerichtsstand'] ?? null]);
    }

    // 3. Authentifizierung
    $stmt = $pdo->prepare("UPDATE kunden_authentifizierung SET email = ?, email_verifiziert = ?, agb_accepted = ?, datenschutz_accepted = ? WHERE kunde_id = ?");
    $stmt->execute([
        $input['email'] ?? '',
        !empty($input['email_verifiziert']) ? 1 : 0,
        !empty($input['agb_accepted']) ? 1 : 0,
        !empty($input['datenschutz_accepted']) ? 1 : 0,
        $id
    ]);

    // Mot de passe (optionnel)
    if (!empty($input['passwort'])) {
        $stmt = $pdo->prepare("UPDATE kunden_authentifizierung SET passwort_hash = ? WHERE kunde_id = ?");
        $stmt->execute([password_hash($input['passwort'], PASSWORD_DEFAULT), $id]);
    }

    // 4. Identifikation
    $stmt = $pdo->prepare("UPDATE kunden_identifikation SET steuernummer = ?, ust_id_nr = ?, auslandskunden = ?, eg_ausland = ? WHERE kunde_id = ?");
    $stmt->execute([
        $input['steuernummer'] ?? null,
        $input['ust_id_nr'] ?? null,
        !empty($input['auslandskunden']) ? 1 : 0,
        !empty($input['eg_ausland']) ? 1 : 0,
        $id
    ]);

    // 5. Klassifizierung
    $stmt = $pdo->prepare("UPDATE kunden_klassifizierung SET kundengruppe = ?, branche = ? WHERE kunde_id = ?");
    $stmt->execute([$input['kundengruppe'] ?? null, $input['branche'] ?? null, $id]);

    // 6. Adressen
    if (isset($input['adressen']) && is_array($input['adressen'])) {
        $stmt = $pdo->prepare("DELETE FROM kunden_adressen WHERE kunde_id = ?");
        $stmt->execute([$id]);
        $stmt = $pdo->prepare("INSERT INTO kunden_adressen (kunde_id, typ, strasse, hausnummer, adresszusatz, plz, ort, bundesland, land, postfach, ist_standard) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($input['adressen'] as $addr) {
            if (empty($addr['strasse']) && empty($addr['ort'])) continue;
            $stmt->execute([$id, $addr['typ'] ?? 'Rechnungsadresse', $addr['strasse'] ?? null, $addr['hausnummer'] ?? null,
                $addr['adresszusatz'] ?? null, $addr['plz'] ?? null, $addr['ort'] ?? null,
                $addr['bundesland'] ?? null, $addr['land'] ?? 'Deutschland', $addr['postfach'] ?? null,
                !empty($addr['ist_standard']) ? 1 : 0]);
        }
    }

    // 7. Kommunikation
    if (isset($input['kommunikation']) && is_array($input['kommunikation'])) {
        $stmt = $pdo->prepare("DELETE FROM kunden_kommunikation WHERE kunde_id = ?");
        $stmt->execute([$id]);
        $stmt = $pdo->prepare("INSERT INTO kunden_kommunikation (kunde_id, typ, wert, bemerkung, ist_standard) VALUES (?, ?, ?, ?, ?)");
        foreach ($input['kommunikation'] as $k) {
            if (empty($k['wert'])) continue;
            $stmt->execute([$id, $k['typ'] ?? 'Telefon', $k['wert'], $k['bemerkung'] ?? null, !empty($k['ist_standard']) ? 1 : 0]);
        }
    }

    // 8. Bankverbindungen
    if (isset($input['bankverbindungen']) && is_array($input['bankverbindungen'])) {
        $stmt = $pdo->prepare("DELETE FROM kunden_bankverbindung WHERE kunde_id = ?");
        $stmt->execute([$id]);
        $stmt = $pdo->prepare("INSERT INTO kunden_bankverbindung (kunde_id, kontoinhaber, iban, bic, bankname, ist_standard) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($input['bankverbindungen'] as $b) {
            if (empty($b['iban'])) continue;
            $stmt->execute([$id, $b['kontoinhaber'] ?? null, preg_replace('/\s+/', '', strtoupper($b['iban'])),
                $b['bic'] ?? null, $b['bankname'] ?? null, !empty($b['ist_standard']) ? 1 : 0]);
        }
    }

    // 9. Rechnungsdaten
    $stmt = $pdo->prepare("DELETE FROM kunden_rechnungsdaten WHERE kunde_id = ?");
    $stmt->execute([$id]);
    $stmt = $pdo->prepare("INSERT INTO kunden_rechnungsdaten (kunde_id, zahlungsweise, versandart, zahlungsziel, gesamtrabatt) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$id, $input['zahlungsweise'] ?? null, $input['versandart'] ?? null,
        !empty($input['zahlungsziel']) ? $input['zahlungsziel'] : null,
        !empty($input['gesamtrabatt']) ? $input['gesamtrabatt'] : null]);

    // 10. Preisgruppen
    if (isset($input['preisgruppen']) && is_array($input['preisgruppen'])) {
        $stmt = $pdo->prepare("DELETE FROM kunden_preisgruppen WHERE kunde_id = ?");
        $stmt->execute([$id]);
        $stmt = $pdo->prepare("INSERT INTO kunden_preisgruppen (kunde_id, preisgruppe, prioritaet) VALUES (?, ?, ?)");
        foreach ($input['preisgruppen'] as $p) {
            if (empty($p['preisgruppe'])) continue;
            $stmt->execute([$id, $p['preisgruppe'], $p['prioritaet'] ?? 0]);
        }
    }

    // 11. Artikelrabatte
    if (isset($input['artikelrabatte']) && is_array($input['artikelrabatte'])) {
        $stmt = $pdo->prepare("DELETE FROM kunden_artikelrabatte WHERE kunde_id = ?");
        $stmt->execute([$id]);
        $stmt = $pdo->prepare("INSERT INTO kunden_artikelrabatte (kunde_id, stufe, prozent) VALUES (?, ?, ?)");
        foreach ($input['artikelrabatte'] as $a) {
            if (empty($a['prozent'])) continue;
            $stmt->execute([$id, $a['stufe'] ?? 1, $a['prozent']]);
        }
    }

    // 12. Mahngebühren
    if (isset($input['mahngebuehren']) && is_array($input['mahngebuehren'])) {
        $stmt = $pdo->prepare("DELETE FROM kunden_mahngebuehren WHERE kunde_id = ?");
        $stmt->execute([$id]);
        $stmt = $pdo->prepare("INSERT INTO kunden_mahngebuehren (kunde_id, stufe, betrag) VALUES (?, ?, ?)");
        foreach ($input['mahngebuehren'] as $m) {
            if (empty($m['betrag'])) continue;
            $stmt->execute([$id, $m['stufe'] ?? 1, $m['betrag']]);
        }
    }

    // 13. Skonto
    if (isset($input['skonto']) && is_array($input['skonto'])) {
        $stmt = $pdo->prepare("DELETE FROM kunden_skonto WHERE kunde_id = ?");
        $stmt->execute([$id]);
        $stmt = $pdo->prepare("INSERT INTO kunden_skonto (kunde_id, stufe, prozent, tage) VALUES (?, ?, ?, ?)");
        foreach ($input['skonto'] as $s) {
            if (empty($s['prozent']) || empty($s['tage'])) continue;
            $stmt->execute([$id, $s['stufe'] ?? 1, $s['prozent'], $s['tage']]);
        }
    }

    // 14. Bemerkungen
    if (isset($input['bemerkungen']) && is_array($input['bemerkungen'])) {
        $stmt = $pdo->prepare("DELETE FROM kunden_bemerkungen WHERE kunde_id = ?");
        $stmt->execute([$id]);
        $stmt = $pdo->prepare("INSERT INTO kunden_bemerkungen (kunde_id, typ, bemerkung, erstellt_am) VALUES (?, ?, ?, NOW())");
        foreach ($input['bemerkungen'] as $b) {
            if (empty($b['bemerkung'])) continue;
            $stmt->execute([$id, $b['typ'] ?? 'internal', $b['bemerkung']]);
        }
    }

    // 15. Zusatzfelder
    if (isset($input['zusatzfelder']) && is_array($input['zusatzfelder'])) {
        $stmt = $pdo->prepare("DELETE FROM kunden_zusatzfelder WHERE kunde_id = ?");
        $stmt->execute([$id]);
        $stmt = $pdo->prepare("INSERT INTO kunden_zusatzfelder (kunde_id, feldname, wert) VALUES (?, ?, ?)");
        foreach ($input['zusatzfelder'] as $z) {
            if (empty($z['feldname'])) continue;
            $stmt->execute([$id, $z['feldname'], $z['wert'] ?? null]);
        }
    }

    // 16. Photo
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
        $kundeDir = $docRoot . '/uploads/kunden/' . $id . '/';
        if (!is_dir($kundeDir)) mkdir($kundeDir, 0755, true);
        $photoName = 'profil_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $targetPath = $kundeDir . $photoName;
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) throw new Exception('Fehler beim Speichern des Fotos.');
        $profilbildPath = '/uploads/kunden/' . $id . '/' . $photoName;
        $stmt = $pdo->prepare("UPDATE kunden SET profilbild = ? WHERE id = ?");
        $stmt->execute([$profilbildPath, $id]);
    }

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Kunde aktualisiert', 'kunde_id' => $id]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    file_put_contents(__DIR__ . '/debug.log', date('Y-m-d H:i:s') . " KUNDEN UPDATE ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}