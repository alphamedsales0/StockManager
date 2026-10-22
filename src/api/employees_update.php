<?php
// employees_update.php
// Met à jour toutes les données d'un employé (identifié par son uid)
// Dernière mise à jour : 2026-10-18
// ✅ austrittsdatum avec validation >= einstellungsdatum
// ✅ Adresse acceptée en objet (adresse) ou tableau (addresses)
// ✅ Chemins uploads via DOCUMENT_ROOT
// ✅ Validation MIME + taille photo + suppression ancienne photo
// ✅ Logging détaillé

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: PUT, POST, OPTIONS");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/database_connect.php';

// ============================================================
// 1. UID
// ============================================================
$employeeUid = $_GET['uid'] ?? '';

if (!$employeeUid || !preg_match('/^[0-9a-f\-]{36}$/i', $employeeUid)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'UID fehlt oder ungültig']);
    exit;
}

// ============================================================
// 2. INPUT (multipart OU JSON)
// ============================================================
$input = [];
if (!empty($_POST['employee'])) {
    $input = json_decode($_POST['employee'], true) ?? [];
} else {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true) ?? [];
}

file_put_contents(
    __DIR__ . '/debug.log',
    date('Y-m-d H:i:s') . " UPDATE INPUT [{$employeeUid}]: " . json_encode($input, JSON_UNESCAPED_UNICODE) . "\n",
    FILE_APPEND
);

// ============================================================
// 3. VALIDATION austrittsdatum >= einstellungsdatum
// ============================================================
if (!empty($input['austrittsdatum']) && !empty($input['einstellungsdatum'])) {
    if (strtotime($input['austrittsdatum']) < strtotime($input['einstellungsdatum'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error'   => 'Austrittsdatum muss nach dem Einstellungsdatum liegen'
        ]);
        exit;
    }
}

// ============================================================
// 4. EMPLOYÉ EXISTE ?
// ============================================================
$stmt = $pdo->prepare("
    SELECT u.id AS benutzer_id, e.id AS mitarbeiter_id, e.foto_pfad
    FROM users u
    INNER JOIN employees e ON e.benutzer_id = u.id
    WHERE e.uid = ?
");
$stmt->execute([$employeeUid]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Mitarbeiter nicht gefunden']);
    exit;
}

$benutzer_id        = (int)$row['benutzer_id'];
$oldPhotoPublicPath = $row['foto_pfad'] ?? null;

$pdo->beginTransaction();

try {
    // =========================================================
    // 1. UPDATE USERS
    // =========================================================
    $updates = [];
    $params  = [];

    $email = trim($input['email'] ?? '');
    if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $updates[] = "email = ?";
        $params[]  = $email;
    }

    $password = $input['password'] ?? null;
    if ($password) {
        $updates[] = "password_hash = ?";
        $params[]  = password_hash($password, PASSWORD_DEFAULT);
    }

    $vorname  = $input['vorname']  ?? null;
    $nachname = $input['nachname'] ?? null;
    if ($vorname !== null || $nachname !== null) {
        $stmtCurrent = $pdo->prepare("SELECT vorname, nachname FROM employees WHERE uid = ?");
        $stmtCurrent->execute([$employeeUid]);
        $current = $stmtCurrent->fetch(PDO::FETCH_ASSOC);
        $newVorname  = $vorname  ?? $current['vorname'];
        $newNachname = $nachname ?? $current['nachname'];
        $fullname    = trim($newVorname . ' ' . $newNachname);
        if (!empty($fullname)) {
            $updates[] = "name = ?";
            $params[]  = $fullname;
        }
    }

    $allowedRoles = ['employee', 'manager', 'admin', 'technician'];
    $role = $input['role'] ?? null;
    if ($role && in_array($role, $allowedRoles, true)) {
        $updates[] = "role = ?";
        $params[]  = $role;
    }

    if (!empty($updates)) {
        $updates[] = "updated_at = NOW()";
        $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
        $params[] = $benutzer_id;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }

    // =========================================================
    // 2. UPDATE EMPLOYEES (avec austrittsdatum)
    // =========================================================
    $stmt = $pdo->prepare("
        UPDATE employees SET
            vorname = ?,
            nachname = ?,
            telefon = ?,
            mobil = ?,
            position = ?,
            abteilung = ?,
            einstellungsdatum = ?,
            austrittsdatum = ?,
            geburtsdatum = ?,
            gehalt = ?,
            notfall_kontakt_name = ?,
            notfall_kontakt_telefon = ?,
            steuer_id = ?,
            sozialversicherungsnummer = ?,
            vertragsart = ?,
            wochenarbeitszeit = ?,
            steuerklasse = ?,
            konfession = ?,
            vorgesetzter = ?,
            aktualisiert_am = NOW()
        WHERE uid = ?
    ");
    $stmt->execute([
        $input['vorname']  ?? null,
        $input['nachname'] ?? null,
        $input['telefon']  ?? null,
        $input['mobil']    ?? null,
        $input['position'] ?? null,
        $input['abteilung'] ?? null,
        !empty($input['einstellungsdatum']) ? $input['einstellungsdatum'] : null,
        !empty($input['austrittsdatum'])    ? $input['austrittsdatum']    : null,
        !empty($input['geburtsdatum'])      ? $input['geburtsdatum']      : null,
        !empty($input['gehalt'])            ? $input['gehalt']            : null,
        $input['notfall_kontakt_name']    ?? null,
        $input['notfall_kontakt_telefon'] ?? null,
        $input['steuer_id'] ?? null,
        $input['sozialversicherungsnummer'] ?? null,
        $input['vertragsart'] ?? null,
        !empty($input['wochenarbeitszeit']) ? $input['wochenarbeitszeit'] : null,
        $input['steuerklasse'] ?? '1',
        $input['konfession']   ?? 'keine',
        $input['vorgesetzter'] ?? null,
        $employeeUid
    ]);

    // =========================================================
    // 3. ADRESSES (remplacement complet)
    // =========================================================
    $stmt = $pdo->prepare("DELETE FROM employees_addresses WHERE mitarbeiter_uid = ? AND ist_aktiv = 1");
    $stmt->execute([$employeeUid]);

    $addressesToSave = [];
    if (isset($input['addresses']) && is_array($input['addresses'])) {
        $addressesToSave = $input['addresses'];
    } elseif (isset($input['adresse']) && is_array($input['adresse']) && !empty($input['adresse']['strasse'])) {
        $addressesToSave = [$input['adresse']];
    }

    file_put_contents(
        __DIR__ . '/debug.log',
        date('Y-m-d H:i:s') . " UPDATE ADDRESSES TO SAVE: " . json_encode($addressesToSave, JSON_UNESCAPED_UNICODE) . "\n",
        FILE_APPEND
    );

    if (!empty($addressesToSave)) {
        $stmt = $pdo->prepare("
            INSERT INTO employees_addresses (
                mitarbeiter_uid, adresstyp, strasse, hausnummer, plz, stadt, land, ist_aktiv
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        foreach ($addressesToSave as $index => $addr) {
            if (empty($addr['strasse']) || empty($addr['hausnummer'])) {
                file_put_contents(
                    __DIR__ . '/debug.log',
                    date('Y-m-d H:i:s') . " ⚠️ UPDATE ADRESSE #{$index} IGNORÉE\n",
                    FILE_APPEND
                );
                continue;
            }
            $stmt->execute([
                $employeeUid,
                $addr['adresstyp'] ?? 'primär',
                $addr['strasse'],
                $addr['hausnummer'],
                $addr['plz']   ?? null,
                $addr['stadt'] ?? null,
                $addr['land']  ?? 'Deutschland',
                isset($addr['ist_aktiv']) ? (int)$addr['ist_aktiv'] : 1
            ]);
            file_put_contents(
                __DIR__ . '/debug.log',
                date('Y-m-d H:i:s') . " ✅ UPDATE ADRESSE #{$index} INSÉRÉE\n",
                FILE_APPEND
            );
        }
    }

    // =========================================================
    // 4. BANK ACCOUNTS (remplacement complet)
    // =========================================================
    if (isset($input['bank_accounts']) && is_array($input['bank_accounts'])) {
        $stmt = $pdo->prepare("DELETE FROM employee_bank_accounts WHERE mitarbeiter_uid = ?");
        $stmt->execute([$employeeUid]);

        $stmt = $pdo->prepare("
            INSERT INTO employee_bank_accounts (
                mitarbeiter_uid, kontoinhaber, iban, bic, bankname, ist_aktiv
            ) VALUES (?, ?, ?, ?, ?, ?)
        ");
        foreach ($input['bank_accounts'] as $account) {
            if (empty($account['iban'])) continue;
            $stmt->execute([
                $employeeUid,
                $account['kontoinhaber'] ?? null,
                preg_replace('/\s+/', '', strtoupper($account['iban'])),
                $account['bic']      ?? null,
                $account['bankname'] ?? null,
                !empty($account['ist_aktiv']) ? 1 : 0
            ]);
        }
    }

    // =========================================================
    // 5. QUALIFICATIONS (remplacement complet)
    // =========================================================
    if (isset($input['qualifications']) && is_array($input['qualifications'])) {
        $stmt = $pdo->prepare("DELETE FROM employee_qualifications WHERE mitarbeiter_uid = ?");
        $stmt->execute([$employeeUid]);

        $stmt = $pdo->prepare("
            INSERT INTO employee_qualifications (
                mitarbeiter_uid, qualifikationstyp, bezeichnung, institution,
                abschlussdatum, gueltig_bis, note, datei_pfad
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        foreach ($input['qualifications'] as $qual) {
            if (empty($qual['bezeichnung'])) continue;
            $stmt->execute([
                $employeeUid,
                $qual['qualifikationstyp'] ?? 'Sonstige',
                $qual['bezeichnung'],
                $qual['institution'] ?? null,
                !empty($qual['abschlussdatum']) ? $qual['abschlussdatum'] : null,
                !empty($qual['gueltig_bis'])    ? $qual['gueltig_bis']    : null,
                $qual['note'] ?? null,
                $qual['datei_pfad'] ?? null
            ]);
        }
    }

    // =========================================================
    // 6. VERSICHERUNG (remplacement, une seule ligne active)
    // =========================================================
    if (isset($input['versicherung_gesellschaft']) || isset($input['versicherung_nummer'])) {
        $stmt = $pdo->prepare("DELETE FROM mitarbeiter_versicherungen WHERE mitarbeiter_uid = ?");
        $stmt->execute([$employeeUid]);

        $stmt = $pdo->prepare("
            INSERT INTO mitarbeiter_versicherungen (
                mitarbeiter_uid, versicherungstyp, versicherungsgesellschaft, versicherungsnummer,
                gueltig_ab, gueltig_bis, beitrag, ist_aktiv
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 1)
        ");
        $stmt->execute([
            $employeeUid,
            $input['versicherung_typ'] ?? 'Krankenversicherung',
            $input['versicherung_gesellschaft'] ?? null,
            $input['versicherung_nummer'] ?? null,
            !empty($input['versicherung_gueltig_ab'])  ? $input['versicherung_gueltig_ab']  : null,
            !empty($input['versicherung_gueltig_bis']) ? $input['versicherung_gueltig_bis'] : null,
            !empty($input['versicherung_beitrag'])     ? $input['versicherung_beitrag']     : null
        ]);
    }

    // =========================================================
    // 7. DOCUMENTS
    // =========================================================
    if (isset($input['documents']) && is_array($input['documents'])) {
        $docRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
        $uploadDir = $docRoot . '/uploads/employees/' . $employeeUid . '/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $stmtDel = $pdo->prepare("DELETE FROM employee_documents WHERE mitarbeiter_uid = ? AND name = ?");
        $stmtIns = $pdo->prepare("
            INSERT INTO employee_documents (
                mitarbeiter_uid, name, typ, datei_pfad, gueltig_bis
            ) VALUES (?, ?, ?, ?, ?)
        ");

        foreach ($input['documents'] as $index => $doc) {
            if (empty($doc['name']) || empty($doc['typ'])) continue;

            $fileKey  = 'document_' . $index;
            $filePath = $doc['datei_pfad'] ?? '';

            if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
                $file    = $_FILES[$fileKey];
                $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $newName = uniqid() . '.' . $ext;
                $target  = $uploadDir . $newName;
                if (move_uploaded_file($file['tmp_name'], $target)) {
                    $filePath = '/uploads/employees/' . $employeeUid . '/' . $newName;
                }
            }

            $stmtDel->execute([$employeeUid, $doc['name']]);
            $stmtIns->execute([
                $employeeUid,
                $doc['name'],
                $doc['typ'],
                $filePath,
                !empty($doc['gueltig_bis']) ? $doc['gueltig_bis'] : null
            ]);
        }
    }

    // =========================================================
    // 8. PHOTO
    // =========================================================
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['photo'];

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowedMimes = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
        ];

        if (!isset($allowedMimes[$mime])) {
            throw new Exception('Ungültiges Bildformat (nur JPG, PNG, WebP, GIF).');
        }
        $ext = $allowedMimes[$mime];

        if ($file['size'] > 5 * 1024 * 1024) {
            throw new Exception('Datei zu groß (max. 5 MB).');
        }

        $docRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
        if (empty($docRoot)) {
            throw new Exception('DOCUMENT_ROOT nicht definiert.');
        }

        $uploadsDir = $docRoot . '/uploads/';
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0755, true);
        }

        $employeesDir = $uploadsDir . 'employees/';
        if (!is_dir($employeesDir)) {
            mkdir($employeesDir, 0755, true);
        }

        $photoDir = $employeesDir . $employeeUid . '/';
        if (!is_dir($photoDir)) {
            mkdir($photoDir, 0755, true);
        }

        // Supprimer l'ancienne photo
        if (!empty($oldPhotoPublicPath)) {
            $oldPhotoFullPath = $docRoot . $oldPhotoPublicPath;
            if (is_file($oldPhotoFullPath)) {
                @unlink($oldPhotoFullPath);
            }
        }

        $photoName  = 'profil_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $targetPath = $photoDir . $photoName;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new Exception('Fehler beim Speichern des Fotos.');
        }

        $photoPublicPath = '/uploads/employees/' . $employeeUid . '/' . $photoName;

        $stmt = $pdo->prepare("UPDATE employees SET foto_pfad = ? WHERE uid = ?");
        $stmt->execute([$photoPublicPath, $employeeUid]);
    }

    // =========================================================
    // COMMIT
    // =========================================================
    $pdo->commit();

    echo json_encode([
        'success'      => true,
        'message'      => 'Mitarbeiter aktualisiert',
        'employee_uid' => $employeeUid
    ]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    file_put_contents(__DIR__ . '/debug.log', "PDO EXCEPTION: " . $e->getMessage() . "\n", FILE_APPEND);

    if (isset($e->errorInfo[1]) && $e->errorInfo[1] == 1062) {
        http_response_code(409);
        echo json_encode(['success' => false, 'error' => 'E-Mail existiert bereits']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Datenbankfehler: ' . $e->getMessage()]);
    }
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    file_put_contents(__DIR__ . '/debug.log', "GENERAL EXCEPTION: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Allgemeiner Fehler: ' . $e->getMessage()]);
}