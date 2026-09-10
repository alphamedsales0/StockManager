<?php
// employees_update.php
// Met à jour toutes les données d'un employé (identifié par son uid)
// Dernière mise à jour : 2025-09-10
// ✅ Adapté à la structure : employees.uid + tables enfants avec mitarbeiter_uid

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

// ----- Récupération de l'identifiant public -----
$employeeUid = $_GET['uid'] ?? '';

if (!$employeeUid || !preg_match('/^[0-9a-f\-]{36}$/i', $employeeUid)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'UID fehlt oder ungültig']);
    exit;
}

// ----- Lecture de l'entrée (multipart OU JSON) -----
$input = [];
if (!empty($_POST['employee'])) {
    $input = json_decode($_POST['employee'], true) ?? [];
} else {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true) ?? [];
}

file_put_contents(
    __DIR__ . '/debug.log',
    date('Y-m-d H:i:s') . " UPDATE INPUT [{$employeeUid}]: " . json_encode($input) . "\n",
    FILE_APPEND
);

// ----- Vérifier que l'employé existe et récupérer benutzer_id -----
$stmt = $pdo->prepare("
    SELECT u.id AS benutzer_id, e.id AS mitarbeiter_id
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

$benutzer_id = (int)$row['benutzer_id'];

$pdo->beginTransaction();

try {
    // =========================================================
    // 1. Mise à jour de la table users
    // =========================================================
    $updates = [];
    $params  = [];

    // Email (validé)
    $email = trim($input['email'] ?? '');
    if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $updates[] = "email = ?";
        $params[]  = $email;
    }

    // Mot de passe (seulement si fourni)
    $password = $input['password'] ?? null;
    if ($password) {
        $updates[] = "password_hash = ?";
        $params[]  = password_hash($password, PASSWORD_DEFAULT);
    }

    // Nom complet (reconstruit depuis vorname/nachname)
    $vorname  = $input['vorname']  ?? null;
    $nachname = $input['nachname'] ?? null;
    if ($vorname !== null || $nachname !== null) {
        $stmtCurrent = $pdo->prepare("SELECT vorname, nachname FROM employees WHERE uid = ?");
        $stmtCurrent->execute([$employeeUid]);
        $current = $stmtCurrent->fetch(PDO::FETCH_ASSOC);
        $newVorname  = $vorname  ?? $current['vorname'];
        $newNachname = $nachname ?? $current['nachname'];
        $fullname = trim($newVorname . ' ' . $newNachname);
        if (!empty($fullname)) {
            $updates[] = "name = ?";
            $params[]  = $fullname;
        }
    }

    // Rôle (whitelist)
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
    // 2. Mise à jour de la table employees
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
    // 3. Adresses : on remplace (DELETE + INSERT)
    // =========================================================
    $stmt = $pdo->prepare("DELETE FROM employees_addresses WHERE mitarbeiter_uid = ? AND ist_aktiv = 1");
    $stmt->execute([$employeeUid]);

    $addressesToSave = [];
    if (isset($input['addresses']) && is_array($input['addresses'])) {
        $addressesToSave = $input['addresses'];
    } elseif (isset($input['adresse']) && !empty($input['adresse']['strasse'])) {
        $addressesToSave = [$input['adresse']];
    }

    if (!empty($addressesToSave)) {
        $stmt = $pdo->prepare("
            INSERT INTO employees_addresses (
                mitarbeiter_uid, adresstyp, strasse, hausnummer, plz, stadt, land, ist_aktiv
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        foreach ($addressesToSave as $addr) {
            if (empty($addr['strasse']) || empty($addr['hausnummer'])) continue;
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
        }
    }

    // =========================================================
    // 4. Comptes bancaires : remplacement complet
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
                $account['iban'],
                $account['bic']      ?? null,
                $account['bankname'] ?? null,
                !empty($account['ist_aktiv']) ? 1 : 0
            ]);
        }
    }

    // =========================================================
    // 5. Qualifications : remplacement complet
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
    // 6. Versicherung : remplacement (une seule ligne active)
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
    // 7. Documents : mise à jour avec upload (si fourni)
    // =========================================================
    if (isset($input['documents']) && is_array($input['documents'])) {
        $uploadDir = __DIR__ . '/uploads/employees/' . $employeeUid . '/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // On ne supprime PAS les documents existants — on en ajoute ou on met à jour
        // Stratégie simple : on supprime les lignes dont le nom correspond, puis on réinsère
        $stmtDel = $pdo->prepare("DELETE FROM employee_documents WHERE mitarbeiter_uid = ? AND name = ?");
        $stmtIns = $pdo->prepare("
            INSERT INTO employee_documents (
                mitarbeiter_uid, name, typ, datei_pfad, gueltig_bis
            ) VALUES (?, ?, ?, ?, ?)
        ");

        foreach ($input['documents'] as $index => $doc) {
            if (empty($doc['name']) || empty($doc['typ'])) continue;

            $fileKey = 'document_' . $index;
            $filePath = $doc['datei_pfad'] ?? '';

            // Si un nouveau fichier a été uploadé, on l'enregistre
            if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES[$fileKey];
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $newName = uniqid() . '.' . $ext;
                $targetPath = $uploadDir . $newName;
                if (move_uploaded_file($file['tmp_name'], $targetPath)) {
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
    // 8. Photo de profil (si uploadée)
    // =========================================================
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $photoDir = __DIR__ . '/uploads/employees/' . $employeeUid . '/';
        if (!is_dir($photoDir)) mkdir($photoDir, 0777, true);

        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $photoName = 'profile.' . $ext;
        $targetPath = $photoDir . $photoName;

        if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetPath)) {
            $stmt = $pdo->prepare("UPDATE employees SET foto_pfad = ? WHERE uid = ?");
            $stmt->execute([
                '/uploads/employees/' . $employeeUid . '/' . $photoName,
                $employeeUid
            ]);
        }
    }

    $pdo->commit();

    echo json_encode([
        'success'      => true,
        'message'      => 'Mitarbeiter aktualisiert',
        'employee_uid' => $employeeUid
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    file_put_contents(__DIR__ . '/debug.log', "PDO EXCEPTION: " . $e->getMessage() . "\n", FILE_APPEND);

    if (isset($e->errorInfo[1]) && $e->errorInfo[1] == 1062) {
        http_response_code(409);
        echo json_encode(['success' => false, 'error' => 'E-Mail existiert bereits']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Datenbankfehler: ' . $e->getMessage()]);
    }
} catch (Throwable $e) {
    $pdo->rollBack();
    file_put_contents(__DIR__ . '/debug.log', "GENERAL EXCEPTION: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Allgemeiner Fehler: ' . $e->getMessage()]);
}