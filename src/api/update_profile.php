<?php
// api/update_profile.php

header('Content-Type: application/json; charset=utf-8');

// ============================================================
// CORS
// ============================================================
$allowed_origins = [
    'https://alpha-med-care.com',
    'http://localhost:5173',
    'http://localhost:3000',
    'http://127.0.0.1:5173',
    'http://127.0.0.1:3000'
];

if (
    isset($_SERVER['HTTP_ORIGIN']) &&
    in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins, true)
) {
    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
    header('Access-Control-Allow-Credentials: true');
} else {
    header('Access-Control-Allow-Origin: *');
}

header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Nur POST erlauben
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Nur POST-Anfragen sind erlaubt.'
    ]);
    exit;
}

// ============================================================
// SESSION
// ============================================================
$isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $isSecure,
    'httponly' => true,
    'samesite' => $isSecure ? 'None' : 'Lax'
]);

session_name('ALPHAMEDCARE');
session_start();

require_once 'database.php';

// ============================================================
// KUNDE-ID ERMITTELN
// ============================================================
$kundeId = null;

if (!empty($_SESSION['kunde_id'])) {
    $kundeId = (int) $_SESSION['kunde_id'];
} elseif (!empty($_SESSION['user_id'])) {
    $stmt = $dbh->prepare("
        SELECT kunde_id
        FROM kunden_authentifizierung
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $kundeId = (int) $row['kunde_id'];
        $_SESSION['kunde_id'] = $kundeId;
    }
}

if (!$kundeId) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Nicht eingeloggt oder Kunde nicht gefunden.'
    ]);
    exit;
}

// ============================================================
// FALL 1 : UPLOAD D'IMAGE (multipart/form-data)
// ============================================================
// Détection : présence du fichier "image" dans $_FILES
if (isset($_FILES['image'])) {

    $response = [
        'success' => false,
        'message' => ''
    ];

    try {
        // --- Vérification erreur upload ---
        if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Upload-Fehler (Code ' . $_FILES['image']['error'] . ').');
        }

        $file = $_FILES['image'];

        // --- MIME réel ---
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowedMimes = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
        ];

        if (!isset($allowedMimes[$mime])) {
            throw new Exception('Ungültiges Dateiformat (nur JPEG, PNG, GIF, WEBP).');
        }

        $ext = $allowedMimes[$mime];

        // --- Taille max 5 MB ---
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new Exception('Datei zu groß (max. 5 MB).');
        }

        // --- Dossier cible ---
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/profilbilder/';
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                throw new Exception('Upload-Verzeichnis konnte nicht erstellt werden.');
            }
        }

        // --- Nom unique ---
        $filename   = 'kunde_' . $kundeId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $targetPath = $uploadDir . $filename;

        // --- Suppression ancienne image ---
        $stmt = $dbh->prepare("SELECT profilbild FROM kunden WHERE id = ?");
        $stmt->execute([$kundeId]);
        $oldImage = $stmt->fetchColumn();

        if ($oldImage) {
            $oldPhysicalPath = $_SERVER['DOCUMENT_ROOT'] . $oldImage;
            if (file_exists($oldPhysicalPath) && is_file($oldPhysicalPath)) {
                @unlink($oldPhysicalPath);
            }
        }

        // --- Déplacement du fichier ---
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new Exception('Fehler beim Speichern der Datei.');
        }

        // --- URL publique + BDD ---
        $relativePath = '/uploads/profilbilder/' . $filename;

        $updateStmt = $dbh->prepare("UPDATE kunden SET profilbild = ? WHERE id = ?");
        $updateStmt->execute([$relativePath, $kundeId]);

        // --- Réponse au format attendu par le front ---
        $response['success'] = true;
        $response['message'] = 'Bild erfolgreich hochgeladen.';
        $response['data']    = [
            'url' => $relativePath
        ];

    } catch (Throwable $e) {
        error_log('[updateProfile::upload] ' . $e->getMessage());
        $response['success'] = false;
        $response['message'] = $e->getMessage();
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// ============================================================
// FALL 2 : SUPPRESSION D'IMAGE (action explicite)
// ============================================================
// Détection : un flag JSON "delete_profile_image" = true
// OU action=deleteProfileImage (fallback)
$rawInput   = file_get_contents('php://input');
$input      = json_decode($rawInput, true);
if (!is_array($input)) {
    $input = [];
}

$action          = $_GET['action'] ?? '';
$deleteImageFlag = !empty($input['delete_profile_image']) || $action === 'deleteProfileImage';

if ($deleteImageFlag && !isset($_FILES['image'])) {

    $response = ['success' => false, 'message' => ''];

    try {
        // --- Récup ancienne image ---
        $stmt = $dbh->prepare("SELECT profilbild FROM kunden WHERE id = ?");
        $stmt->execute([$kundeId]);
        $oldImage = $stmt->fetchColumn();

        if ($oldImage) {
            $oldPhysicalPath = $_SERVER['DOCUMENT_ROOT'] . $oldImage;
            if (file_exists($oldPhysicalPath) && is_file($oldPhysicalPath)) {
                @unlink($oldPhysicalPath);
            }
        }

        // --- Mise à NULL en BDD ---
        $updateStmt = $dbh->prepare("UPDATE kunden SET profilbild = NULL WHERE id = ?");
        $updateStmt->execute([$kundeId]);

        $response['success'] = true;
        $response['message'] = 'Bild erfolgreich gelöscht.';

    } catch (Throwable $e) {
        error_log('[updateProfile::delete] ' . $e->getMessage());
        $response['success'] = false;
        $response['message'] = 'Fehler beim Löschen: ' . $e->getMessage();
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// ============================================================
// FALL 3 : UPDATE CLASSIQUE (JSON)
// ============================================================
$response = [
    'success' => false,
    'message' => ''
];

try {
    $dbh->beginTransaction();

    // ========================================================
    // 1. ADRESSEN
    // ========================================================
    if (isset($input['addresses']) && is_array($input['addresses'])) {

        $deleteStmt = $dbh->prepare("
            DELETE FROM kunden_adressen
            WHERE kunde_id = ?
        ");
        $deleteStmt->execute([$kundeId]);

        $insertStmt = $dbh->prepare("
            INSERT INTO kunden_adressen
            (
                kunde_id,
                typ,
                strasse,
                hausnummer,
                adresszusatz,
                plz,
                ort,
                bundesland,
                land,
                postfach,
                ist_standard
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($input['addresses'] as $addr) {
            $insertStmt->execute([
                $kundeId,
                $addr['typ'] ?? 'Rechnungsadresse',
                $addr['strasse'] ?? '',
                $addr['hausnummer'] ?? '',
                $addr['adresszusatz'] ?? '',
                $addr['plz'] ?? '',
                $addr['ort'] ?? '',
                $addr['bundesland'] ?? '',
                $addr['land'] ?? 'Deutschland',
                $addr['postfach'] ?? '',
                isset($addr['ist_standard']) ? (int) $addr['ist_standard'] : 0
            ]);
        }
    }

    // ========================================================
    // 2. KOMMUNIKATION
    // ========================================================
    if (isset($input['kommunikation']) && is_array($input['kommunikation'])) {

        $deleteStmt = $dbh->prepare("
            DELETE FROM kunden_kommunikation
            WHERE kunde_id = ?
        ");
        $deleteStmt->execute([$kundeId]);

        $insertStmt = $dbh->prepare("
            INSERT INTO kunden_kommunikation
            (
                kunde_id,
                typ,
                wert,
                bemerkung,
                ist_standard
            )
            VALUES (?, ?, ?, ?, ?)
        ");

        foreach ($input['kommunikation'] as $comm) {
            $insertStmt->execute([
                $kundeId,
                $comm['typ'] ?? '',
                $comm['wert'] ?? '',
                $comm['bemerkung'] ?? '',
                isset($comm['ist_standard']) ? (int) $comm['ist_standard'] : 0
            ]);
        }
    }

    // ========================================================
    // 3. PERSONENDATEN
    // ========================================================
    if (isset($input['person']) && is_array($input['person'])) {

        $p = $input['person'];

        $checkStmt = $dbh->prepare("
            SELECT id FROM kunden_personendaten WHERE kunde_id = ? LIMIT 1
        ");
        $checkStmt->execute([$kundeId]);

        if ($checkStmt->fetch()) {
            $updateStmt = $dbh->prepare("
                UPDATE kunden_personendaten
                SET anrede = ?, vorname = ?, nachname = ?
                WHERE kunde_id = ?
            ");
            $updateStmt->execute([
                $p['anrede'] ?? null,
                $p['vorname'] ?? null,
                $p['nachname'] ?? null,
                $kundeId
            ]);
        } else {
            $insertStmt = $dbh->prepare("
                INSERT INTO kunden_personendaten
                (kunde_id, anrede, vorname, nachname)
                VALUES (?, ?, ?, ?)
            ");
            $insertStmt->execute([
                $kundeId,
                $p['anrede'] ?? null,
                $p['vorname'] ?? null,
                $p['nachname'] ?? null
            ]);
        }
    }

    // ========================================================
    // 4. GEWERBEDATEN
    // ========================================================
    if (isset($input['gewerbe']) && is_array($input['gewerbe'])) {

        $g = $input['gewerbe'];

        $checkStmt = $dbh->prepare("
            SELECT id FROM kunden_gewerbedaten WHERE kunde_id = ? LIMIT 1
        ");
        $checkStmt->execute([$kundeId]);

        if ($checkStmt->fetch()) {
            $updateStmt = $dbh->prepare("
                UPDATE kunden_gewerbedaten
                SET firma = ?, zusatz = ?, kontakt = ?,
                    hrb_register_nummer = ?, hrb_gerichtsstand = ?
                WHERE kunde_id = ?
            ");
            $updateStmt->execute([
                $g['firma'] ?? null,
                $g['zusatz'] ?? null,
                $g['kontakt'] ?? null,
                $g['hrb_register_nummer'] ?? null,
                $g['hrb_gerichtsstand'] ?? null,
                $kundeId
            ]);
        } else {
            $insertStmt = $dbh->prepare("
                INSERT INTO kunden_gewerbedaten
                (kunde_id, firma, zusatz, kontakt, hrb_register_nummer, hrb_gerichtsstand)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $insertStmt->execute([
                $kundeId,
                $g['firma'] ?? null,
                $g['zusatz'] ?? null,
                $g['kontakt'] ?? null,
                $g['hrb_register_nummer'] ?? null,
                $g['hrb_gerichtsstand'] ?? null
            ]);
        }
    }

    // ========================================================
    // 5. IDENTIFIKATION
    // ========================================================
    if (isset($input['ident']) && is_array($input['ident'])) {

        $i = $input['ident'];

        $checkStmt = $dbh->prepare("
            SELECT id FROM kunden_identifikation WHERE kunde_id = ? LIMIT 1
        ");
        $checkStmt->execute([$kundeId]);

        if ($checkStmt->fetch()) {
            $updateStmt = $dbh->prepare("
                UPDATE kunden_identifikation
                SET steuernummer = ?, ust_id_nr = ?,
                    auslandskunden = ?, eg_ausland = ?
                WHERE kunde_id = ?
            ");
            $updateStmt->execute([
                $i['steuernummer'] ?? null,
                $i['ust_id_nr'] ?? null,
                isset($i['auslandskunden']) ? (int) $i['auslandskunden'] : 0,
                isset($i['eg_ausland']) ? (int) $i['eg_ausland'] : 0,
                $kundeId
            ]);
        } else {
            $insertStmt = $dbh->prepare("
                INSERT INTO kunden_identifikation
                (kunde_id, steuernummer, ust_id_nr, auslandskunden, eg_ausland)
                VALUES (?, ?, ?, ?, ?)
            ");
            $insertStmt->execute([
                $kundeId,
                $i['steuernummer'] ?? null,
                $i['ust_id_nr'] ?? null,
                isset($i['auslandskunden']) ? (int) $i['auslandskunden'] : 0,
                isset($i['eg_ausland']) ? (int) $i['eg_ausland'] : 0
            ]);
        }
    }

    // ========================================================
    // 6. KLASSIFIZIERUNG
    // ========================================================
    if (isset($input['klass']) && is_array($input['klass'])) {

        $k = $input['klass'];

        $checkStmt = $dbh->prepare("
            SELECT id FROM kunden_klassifizierung WHERE kunde_id = ? LIMIT 1
        ");
        $checkStmt->execute([$kundeId]);

        if ($checkStmt->fetch()) {
            $updateStmt = $dbh->prepare("
                UPDATE kunden_klassifizierung
                SET kundengruppe = ?, branche = ?
                WHERE kunde_id = ?
            ");
            $updateStmt->execute([
                $k['kundengruppe'] ?? null,
                $k['branche'] ?? null,
                $kundeId
            ]);
        } else {
            $insertStmt = $dbh->prepare("
                INSERT INTO kunden_klassifizierung
                (kunde_id, kundengruppe, branche)
                VALUES (?, ?, ?)
            ");
            $insertStmt->execute([
                $kundeId,
                $k['kundengruppe'] ?? null,
                $k['branche'] ?? null
            ]);
        }
    }

    // ========================================================
    // 7. RECHNUNGSDATEN
    // ========================================================
    if (isset($input['rechnung']) && is_array($input['rechnung'])) {

        $r = $input['rechnung'];

        $checkStmt = $dbh->prepare("
            SELECT id FROM kunden_rechnungsdaten WHERE kunde_id = ? LIMIT 1
        ");
        $checkStmt->execute([$kundeId]);

        if ($checkStmt->fetch()) {
            $updateStmt = $dbh->prepare("
                UPDATE kunden_rechnungsdaten
                SET zahlungsweise = ?, versandart = ?,
                    zahlungsziel = ?, gesamtrabatt = ?
                WHERE kunde_id = ?
            ");
            $updateStmt->execute([
                $r['zahlungsweise'] ?? null,
                $r['versandart'] ?? null,
                $r['zahlungsziel'] ?? null,
                $r['gesamtrabatt'] ?? null,
                $kundeId
            ]);
        } else {
            $insertStmt = $dbh->prepare("
                INSERT INTO kunden_rechnungsdaten
                (kunde_id, zahlungsweise, versandart, zahlungsziel, gesamtrabatt)
                VALUES (?, ?, ?, ?, ?)
            ");
            $insertStmt->execute([
                $kundeId,
                $r['zahlungsweise'] ?? null,
                $r['versandart'] ?? null,
                $r['zahlungsziel'] ?? null,
                $r['gesamtrabatt'] ?? null
            ]);
        }
    }

    // ========================================================
    // 8. BANKVERBINDUNGEN
    // ========================================================
    if (isset($input['bank']) && is_array($input['bank'])) {

        $deleteStmt = $dbh->prepare("
            DELETE FROM kunden_bankverbindung WHERE kunde_id = ?
        ");
        $deleteStmt->execute([$kundeId]);

        $insertStmt = $dbh->prepare("
            INSERT INTO kunden_bankverbindung
            (kunde_id, bankname, kontoinhaber, iban, bic, ist_standard)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        foreach ($input['bank'] as $bank) {
            $insertStmt->execute([
                $kundeId,
                $bank['bankname'] ?? '',
                $bank['kontoinhaber'] ?? '',
                $bank['iban'] ?? '',
                $bank['bic'] ?? '',
                isset($bank['ist_standard']) ? (int) $bank['ist_standard'] : 0
            ]);
        }
    }

    // ========================================================
    // 9. PREISGRUPPEN
    // ========================================================
    if (isset($input['preisgruppen']) && is_array($input['preisgruppen'])) {

        $deleteStmt = $dbh->prepare("
            DELETE FROM kunden_preisgruppen WHERE kunde_id = ?
        ");
        $deleteStmt->execute([$kundeId]);

        $insertStmt = $dbh->prepare("
            INSERT INTO kunden_preisgruppen
            (kunde_id, preisgruppe, prioritaet)
            VALUES (?, ?, ?)
        ");

        foreach ($input['preisgruppen'] as $pg) {
            $insertStmt->execute([
                $kundeId,
                $pg['preisgruppe'] ?? '',
                $pg['prioritaet'] ?? 0
            ]);
        }
    }

    // ========================================================
    // 10. ARTIKELRABATTE
    // ========================================================
    if (isset($input['artikelrabatte']) && is_array($input['artikelrabatte'])) {

        $deleteStmt = $dbh->prepare("
            DELETE FROM kunden_artikelrabatte WHERE kunde_id = ?
        ");
        $deleteStmt->execute([$kundeId]);

        $insertStmt = $dbh->prepare("
            INSERT INTO kunden_artikelrabatte
            (kunde_id, stufe, prozent)
            VALUES (?, ?, ?)
        ");

        foreach ($input['artikelrabatte'] as $ar) {
            $insertStmt->execute([
                $kundeId,
                $ar['stufe'] ?? 0,
                $ar['prozent'] ?? 0.00
            ]);
        }
    }

    // ========================================================
    // 11. MAHNGEBÜHREN
    // ========================================================
    if (isset($input['mahngebuehren']) && is_array($input['mahngebuehren'])) {

        $deleteStmt = $dbh->prepare("
            DELETE FROM kunden_mahngebuehren WHERE kunde_id = ?
        ");
        $deleteStmt->execute([$kundeId]);

        $insertStmt = $dbh->prepare("
            INSERT INTO kunden_mahngebuehren
            (kunde_id, stufe, betrag)
            VALUES (?, ?, ?)
        ");

        foreach ($input['mahngebuehren'] as $mg) {
            $insertStmt->execute([
                $kundeId,
                $mg['stufe'] ?? 0,
                $mg['betrag'] ?? 0.00
            ]);
        }
    }

    // ========================================================
    // 12. SKONTO
    // ========================================================
    if (isset($input['skonto']) && is_array($input['skonto'])) {

        $deleteStmt = $dbh->prepare("
            DELETE FROM kunden_skonto WHERE kunde_id = ?
        ");
        $deleteStmt->execute([$kundeId]);

        $insertStmt = $dbh->prepare("
            INSERT INTO kunden_skonto
            (kunde_id, stufe, prozent, tage)
            VALUES (?, ?, ?, ?)
        ");

        foreach ($input['skonto'] as $sk) {
            $insertStmt->execute([
                $kundeId,
                $sk['stufe'] ?? 0,
                $sk['prozent'] ?? 0.00,
                $sk['tage'] ?? 0
            ]);
        }
    }

    // ========================================================
    // 13. BEMERKUNGEN
    // ========================================================
    if (isset($input['bemerkungen']) && is_array($input['bemerkungen'])) {

        $deleteStmt = $dbh->prepare("
            DELETE FROM kunden_bemerkungen WHERE kunde_id = ?
        ");
        $deleteStmt->execute([$kundeId]);

        $insertStmt = $dbh->prepare("
            INSERT INTO kunden_bemerkungen
            (kunde_id, bemerkung, typ)
            VALUES (?, ?, ?)
        ");

        foreach ($input['bemerkungen'] as $bem) {

            $bemerkung = trim($bem['bemerkung'] ?? '');
            if ($bemerkung === '') continue;

            $typ = isset($bem['typ']) && $bem['typ'] === 'customer' ? 'customer' : 'internal';

            $insertStmt->execute([
                $kundeId,
                $bemerkung,
                $typ
            ]);
        }
    }

    // ========================================================
    // 14. ZUSATZFELDER
    // ========================================================
    if (isset($input['zusatzfelder']) && is_array($input['zusatzfelder'])) {

        $deleteStmt = $dbh->prepare("
            DELETE FROM kunden_zusatzfelder WHERE kunde_id = ?
        ");
        $deleteStmt->execute([$kundeId]);

        $insertStmt = $dbh->prepare("
            INSERT INTO kunden_zusatzfelder
            (kunde_id, feldname, wert)
            VALUES (?, ?, ?)
        ");

        foreach ($input['zusatzfelder'] as $zf) {

            $feldname = trim($zf['feldname'] ?? '');
            $wert     = trim($zf['wert'] ?? '');

            if ($feldname === '') continue;

            $insertStmt->execute([
                $kundeId,
                $feldname,
                $wert
            ]);
        }
    }

    // ========================================================
    // COMMIT
    // ========================================================
    $dbh->commit();

    $response['success'] = true;
    $response['message'] = 'Profil erfolgreich aktualisiert.';

} catch (Throwable $e) {

    if ($dbh->inTransaction()) {
        $dbh->rollBack();
    }

    http_response_code(500);
    $response['success'] = false;
    $response['message'] = 'Fehler beim Aktualisieren des Profils.';

    error_log('Update profile error: ' . $e->getMessage());
}

// ============================================================
// RESPONSE
// ============================================================
echo json_encode($response, JSON_UNESCAPED_UNICODE);