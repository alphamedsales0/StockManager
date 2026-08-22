<?php
// employees_create.php
// Crée un nouvel employé avec toutes ses données
// Dernière mise à jour : 2025-08-18

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/database_connect.php';

// PHPMailer (optionnel)
$usePHPMailer = false;
if (file_exists(__DIR__ . '/phpmailer/src/PHPMailer.php')) {
    require __DIR__ . '/phpmailer/src/Exception.php';
    require __DIR__ . '/phpmailer/src/PHPMailer.php';
    require __DIR__ . '/phpmailer/src/SMTP.php';
    $usePHPMailer = true;
}

$config = [];
if (file_exists(__DIR__ . '/config.php')) {
    $config = require __DIR__ . '/config.php';
}

// ----- Hilfsfunktion für fehlersicheres EXECUTE mit Logging -----
function executeWithCheck($pdo, $sql, $params = [])
{
    $placeholderCount = substr_count($sql, '?');
    $paramCount = count($params);

    file_put_contents(
        __DIR__ . '/debug.log',
        date('Y-m-d H:i:s') .
        "\nSQL: {$sql}\n" .
        "PLACEHOLDERS: {$placeholderCount}\n" .
        "PARAMS COUNT: {$paramCount}\n" .
        "PARAMS: " . json_encode($params, JSON_UNESCAPED_UNICODE) . "\n",
        FILE_APPEND
    );

    if ($placeholderCount !== $paramCount) {
        throw new Exception(
            "SQL Placeholder Fehler: {$placeholderCount} Platzhalter, aber {$paramCount} Parameter."
        );
    }

    $stmt = $pdo->prepare($sql);

    if (!$stmt->execute($params)) {
        $errorInfo = $stmt->errorInfo();

        file_put_contents(
            __DIR__ . '/debug.log',
            date('Y-m-d H:i:s') .
            " SQL ERROR: " . json_encode($errorInfo) . "\n",
            FILE_APPEND
        );

        throw new Exception(
            "SQL-Fehler: " . ($errorInfo[2] ?? 'Unbekannter Fehler')
        );
    }

    return $stmt;
}

// ----- Eingabe verarbeiten -----
$employeeData = null;
if (isset($_POST['employee'])) {
    $employeeData = json_decode($_POST['employee'], true);
} else {
    $rawInput = file_get_contents('php://input');
    $employeeData = json_decode($rawInput, true);
}

file_put_contents(__DIR__ . '/debug.log', date('Y-m-d H:i:s') . " INPUT: " . json_encode($employeeData) . "\n", FILE_APPEND);

$email = trim($employeeData['email'] ?? '');
$vorname = trim($employeeData['vorname'] ?? '');
$nachname = trim($employeeData['nachname'] ?? '');

if (!$email || !$vorname || !$nachname) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'E‑Mail, Vorname und Nachname sind erforderlich']);
    exit;
}

// ----- Hilfsfunktionen -----
function generateEmployeeNumber($pdo) {
    $prefix = 'EMP-';
    $date = date('Ymd');
    $sql = "SELECT MAX(CAST(SUBSTRING(mitarbeiter_nummer, LENGTH(?) + 1 + 8 + 1) AS UNSIGNED)) 
            FROM employees 
            WHERE mitarbeiter_nummer LIKE ?";
    $stmt = $pdo->prepare($sql);
    $likePattern = $prefix . $date . '-%';
    $stmt->execute([$prefix, $likePattern]);
    $max = $stmt->fetchColumn();
    $next = ($max ? $max + 1 : 1);
    $number = str_pad($next, 4, '0', STR_PAD_LEFT);
    return $prefix . $date . '-' . $number;
}

function generatePassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+-=';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $password;
}

// Génération du username
$fullname = $vorname . ' ' . $nachname;
$firstPart = strtok($vorname, ' ');
$baseUsername = strtolower($firstPart . '.' . $nachname);
$username = $baseUsername;
$plainPassword = generatePassword();

// Vérifier l'unicité du username (avec suffixe si nécessaire)
$stmtCheck = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$i = 1;
while (true) {
    $stmtCheck->execute([$username]);
    if (!$stmtCheck->fetch()) {
        break;
    }
    $i++;
    $username = $baseUsername . $i;
}

$pdo->beginTransaction();

try {
    // 1. User anlegen – mit name (complet) und username
    $role = !empty($employeeData['role']) ? $employeeData['role'] : 'employee';
    $sql = "INSERT INTO users (name, username, email, password_hash, role, is_active, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, 1, NOW(), NOW())";
    executeWithCheck($pdo, $sql, [
        $fullname,
        $username,
        $email,
        password_hash($plainPassword, PASSWORD_DEFAULT),
        $role
    ]);
    $benutzer_id = $pdo->lastInsertId();

    // 2. Mitarbeiternummer generieren
    $mitarbeiter_nummer = generateEmployeeNumber($pdo);

    // 3. Employee anlegen – avec vorgesetzter, steuerklasse, konfession
    $sql = "
INSERT INTO employees (
    benutzer_id,
    mitarbeiter_nummer,
    vorname,
    nachname,
    telefon,
    mobil,
    position,
    abteilung,
    einstellungsdatum,
    geburtsdatum,
    gehalt,
    notfall_kontakt_name,
    notfall_kontakt_telefon,
    steuer_id,
    sozialversicherungsnummer,
    vertragsart,
    wochenarbeitszeit,
    steuerklasse,
    konfession,
    vorgesetzter,
    aktualisiert_am
) VALUES (
    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
    ?, ?, ?, ?, ?, ?, ?, ?, ?,
    NOW()
)";
    $params = [
        $benutzer_id,
        $mitarbeiter_nummer,
        $vorname,
        $nachname,
        empty($employeeData['telefon']) ? null : $employeeData['telefon'],
        empty($employeeData['mobil']) ? null : $employeeData['mobil'],
        empty($employeeData['position']) ? null : $employeeData['position'],
        empty($employeeData['abteilung']) ? null : $employeeData['abteilung'],
        empty($employeeData['einstellungsdatum']) ? null : $employeeData['einstellungsdatum'],
        empty($employeeData['geburtsdatum']) ? null : $employeeData['geburtsdatum'],
        empty($employeeData['gehalt']) ? null : $employeeData['gehalt'],
        empty($employeeData['notfall_kontakt_name']) ? null : $employeeData['notfall_kontakt_name'],
        empty($employeeData['notfall_kontakt_telefon']) ? null : $employeeData['notfall_kontakt_telefon'],
        empty($employeeData['steuer_id']) ? null : $employeeData['steuer_id'],
        empty($employeeData['sozialversicherungsnummer']) ? null : $employeeData['sozialversicherungsnummer'],
        empty($employeeData['vertragsart']) ? null : $employeeData['vertragsart'],
        empty($employeeData['wochenarbeitszeit']) ? null : $employeeData['wochenarbeitszeit'],
        empty($employeeData['steuerklasse']) ? '1' : $employeeData['steuerklasse'],
        empty($employeeData['konfession']) ? 'keine' : $employeeData['konfession'],
        empty($employeeData['vorgesetzter']) ? null : $employeeData['vorgesetzter']
    ];
    executeWithCheck($pdo, $sql, $params);
    $mitarbeiter_id = $pdo->lastInsertId();

    // 4. Adresse (primär)
    $addr = $employeeData['adresse'] ?? null;
    if ($addr && !empty($addr['strasse']) && !empty($addr['hausnummer'])) {
        $sql = "INSERT INTO employees_addresses (
                    mitarbeiter_id, adresstyp, strasse, hausnummer, plz, stadt, land, ist_aktiv
                ) VALUES (?, 'primär', ?, ?, ?, ?, ?, 1)";
        executeWithCheck($pdo, $sql, [
            $mitarbeiter_id,
            $addr['strasse'],
            $addr['hausnummer'],
            $addr['plz'] ?? null,
            $addr['stadt'] ?? null,
            $addr['land'] ?? 'Deutschland'
        ]);
    }

    // 5. Bankverbindungen
    if (!empty($employeeData['bank_accounts']) && is_array($employeeData['bank_accounts'])) {
        $sql = "INSERT INTO employee_bank_accounts (
                    mitarbeiter_id, kontoinhaber, iban, bic, bankname, ist_aktiv
                ) VALUES (?, ?, ?, ?, ?, ?)";
        foreach ($employeeData['bank_accounts'] as $account) {
            if (!empty($account['iban'])) {
                executeWithCheck($pdo, $sql, [
                    $mitarbeiter_id,
                    $account['kontoinhaber'] ?? null,
                    $account['iban'],
                    $account['bic'] ?? null,
                    $account['bankname'] ?? null,
                    !empty($account['ist_aktiv']) ? 1 : 0
                ]);
            }
        }
    }

    // 6. Qualifikationen
    if (!empty($employeeData['qualifications']) && is_array($employeeData['qualifications'])) {
        $sql = "INSERT INTO employee_qualifications (
                    mitarbeiter_id, qualifikationstyp, bezeichnung, institution,
                    abschlussdatum, gueltig_bis, note, datei_pfad
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        foreach ($employeeData['qualifications'] as $qual) {
            if (!empty($qual['bezeichnung'])) {
                executeWithCheck($pdo, $sql, [
                    $mitarbeiter_id,
                    $qual['qualifikationstyp'] ?? 'Sonstige',
                    $qual['bezeichnung'],
                    $qual['institution'] ?? null,
                    !empty($qual['abschlussdatum']) ? $qual['abschlussdatum'] : null,
                    !empty($qual['gueltig_bis']) ? $qual['gueltig_bis'] : null,
                    $qual['note'] ?? null,
                    $qual['datei_pfad'] ?? null
                ]);
            }
        }
    }

    // 7. Dokumente (avec upload)
    if (!empty($employeeData['documents']) && is_array($employeeData['documents'])) {
        $uploadDir = __DIR__ . '/uploads/employees/' . $mitarbeiter_id . '/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $sql = "INSERT INTO employee_documents (
                    mitarbeiter_id, name, typ, datei_pfad, gueltig_bis
                ) VALUES (?, ?, ?, ?, ?)";
        foreach ($employeeData['documents'] as $index => $doc) {
            if (empty($doc['name']) || empty($doc['typ'])) continue;

            $fileKey = 'document_' . $index;
            $filePath = '';
            if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES[$fileKey];
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $newName = uniqid() . '.' . $ext;
                $targetPath = $uploadDir . $newName;
                if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                    $filePath = '/uploads/employees/' . $mitarbeiter_id . '/' . $newName;
                }
            }

            executeWithCheck($pdo, $sql, [
                $mitarbeiter_id,
                $doc['name'],
                $doc['typ'],
                $filePath,
                !empty($doc['gueltig_bis']) ? $doc['gueltig_bis'] : null
            ]);
        }
    }

    // 8. Versicherung (in mitarbeiter_versicherungen)
    $versicherung_typ = $employeeData['versicherung_typ'] ?? null;
    $versicherung_gesellschaft = $employeeData['versicherung_gesellschaft'] ?? null;
    $versicherung_nummer = $employeeData['versicherung_nummer'] ?? null;
    if ($versicherung_gesellschaft || $versicherung_nummer) {
        $sql = "INSERT INTO mitarbeiter_versicherungen (
                    mitarbeiter_id, versicherungstyp, versicherungsgesellschaft, versicherungsnummer,
                    gueltig_ab, gueltig_bis, beitrag, ist_aktiv
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 1)";
        executeWithCheck($pdo, $sql, [
            $mitarbeiter_id,
            $versicherung_typ ?? 'Krankenversicherung',
            $versicherung_gesellschaft,
            $versicherung_nummer,
            !empty($employeeData['versicherung_gueltig_ab']) ? $employeeData['versicherung_gueltig_ab'] : null,
            !empty($employeeData['versicherung_gueltig_bis']) ? $employeeData['versicherung_gueltig_bis'] : null,
            !empty($employeeData['versicherung_beitrag']) ? $employeeData['versicherung_beitrag'] : null
        ]);
    }

    // 9. Foto speichern
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $photoDir = __DIR__ . '/uploads/employees/' . $mitarbeiter_id . '/';
        if (!is_dir($photoDir)) mkdir($photoDir, 0777, true);
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $photoName = 'profile.' . $ext;
        $targetPath = $photoDir . $photoName;
        move_uploaded_file($_FILES['photo']['tmp_name'], $targetPath);
        // Optionnel : enregistrer le chemin dans employees (si colonne foto_pfad existe)
        // $sql = "UPDATE employees SET foto_pfad = ? WHERE id = ?";
        // executeWithCheck($pdo, $sql, ['/uploads/employees/' . $mitarbeiter_id . '/' . $photoName, $mitarbeiter_id]);
    }

    $pdo->commit();

    // ----- E‑Mail senden (avec PHPMailer ou fallback) -----
    $mailSent = false;
    $mailError = null;
    $subject = 'Ihre Zugangsdaten für das Mitarbeiterportal';
    $htmlBody = buildCredentialsEmail($vorname, $nachname, $username, $plainPassword);
    $textBody = "Guten Tag $vorname $nachname,\n\nIhr Benutzername: $username\nIhr Passwort: $plainPassword\n\nBitte ändern Sie es nach dem ersten Login.";

    if ($usePHPMailer && !empty($config)) {
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = $config['smtp_host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $config['smtp_username'];
            $mail->Password   = $config['smtp_password'];
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $config['smtp_port'] ?? 587;
            $mail->CharSet    = 'UTF-8';
            $mail->Encoding   = 'base64';
            $mail->setFrom($config['smtp_username'], 'Alpha Med Care Service');
            $mail->addAddress($email);
            $mail->Subject = $subject;
            $mail->isHTML(true);
            $mail->Body    = $htmlBody;
            $mail->AltBody = $textBody;
            $mail->send();
            $mailSent = true;
        } catch (Exception $e) {
            $mailError = $e->getMessage();
            $mailSent = false;
        }
    }

    if (!$mailSent) {
        $headers = "From: service@alpha-med-care.com\r\n";
        $headers .= "Reply-To: service@alpha-med-care.com\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $mailSent = @mail($email, $subject, $htmlBody, $headers);
        if (!$mailSent) {
            $headers = "Content-Type: text/plain; charset=UTF-8\r\n";
            $mailSent = @mail($email, $subject, $textBody, $headers);
        }
    }

    file_put_contents(__DIR__ . '/debug.log', "Mail sent: " . ($mailSent ? 'yes' : 'no') . "\n", FILE_APPEND);

    echo json_encode([
        'success' => true,
        'message' => 'Mitarbeiter angelegt, E‑Mail versendet',
        'id' => $benutzer_id,
        'mitarbeiter_nummer' => $mitarbeiter_nummer,
        'username' => $username,
        'mail_sent' => $mailSent,
        'mail_error' => $mailError
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    file_put_contents(__DIR__ . '/debug.log', "PDO EXCEPTION: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Datenbankfehler: ' . $e->getMessage()]);
} catch (Throwable $e) {
    $pdo->rollBack();
    file_put_contents(__DIR__ . '/debug.log', "GENERAL EXCEPTION: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Allgemeiner Fehler: ' . $e->getMessage()]);
}

// ============================================================
// Funktion für die HTML-E-Mail (unverändert)
// ============================================================
function buildCredentialsEmail($vorname, $nachname, $username, $password) {
    return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ihre Zugangsdaten</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; background-color: #f4f7fc; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 30px auto; background: #ffffff; border-radius: 12px; box-shadow: 0 8px 20px rgba(0,0,0,0.05); overflow: hidden; }
        .header { background: linear-gradient(135deg, #0f172a, #1e3c72); padding: 30px 25px; text-align: center; color: white; }
        .header h1 { margin: 0; font-size: 24px; font-weight: 600; letter-spacing: 0.5px; }
        .header p { margin: 8px 0 0; opacity: 0.8; font-size: 14px; }
        .content { padding: 30px 25px; }
        .greeting { font-size: 18px; font-weight: 600; color: #1e293b; margin-bottom: 10px; }
        .message { font-size: 16px; line-height: 1.6; color: #334155; margin-bottom: 20px; }
        .credentials { background: #f8fafc; border-left: 6px solid #0f172a; padding: 16px 20px; border-radius: 8px; margin: 20px 0; }
        .credentials .label { font-size: 14px; color: #64748b; }
        .credentials .value { font-size: 20px; font-weight: 700; color: #0f172a; margin-top: 4px; font-family: monospace; word-break: break-all; }
        .credentials .row { margin-bottom: 12px; }
        .credentials .row:last-child { margin-bottom: 0; }
        .btn { display: inline-block; background: #0f172a; color: white !important; text-decoration: none; padding: 12px 28px; border-radius: 6px; font-weight: 600; font-size: 16px; margin-top: 10px; }
        .btn:hover { background: #1e293b; }
        .footer { background: #f8fafc; padding: 20px 25px; text-align: center; font-size: 13px; color: #94a3b8; border-top: 1px solid #e2e8f0; }
        .footer a { color: #0f172a; text-decoration: none; }
        .footer a:hover { text-decoration: underline; }
        .footer img { max-width: 120px; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>alpha med care</h1>
            <p>Ihr Mitarbeiterkonto wurde eingerichtet</p>
        </div>
        <div class="content">
            <div class="greeting">Guten Tag $vorname $nachname,</div>
            <div class="message">
                wir haben für Sie ein Mitarbeiterkonto erstellt. Mit den folgenden Zugangsdaten können Sie sich im Portal anmelden.
            </div>
            <div class="credentials">
                <div class="row">
                    <div class="label">Benutzername</div>
                    <div class="value">$username</div>
                </div>
                <div class="row">
                    <div class="label">Passwort</div>
                    <div class="value">$password</div>
                </div>
            </div>
            <div style="text-align: center; margin: 25px 0 10px;">
                <a href="https://alpha-med-care.com/login" class="btn">Zum Login</a>
            </div>
            <p style="font-size: 14px; color: #64748b; margin-top: 20px; text-align: center;">
                Bitte ändern Sie Ihr Passwort nach dem ersten Login.
            </p>
            <p style="font-size: 14px; color: #64748b; text-align: center;">
                Bei Fragen wenden Sie sich an Ihren Administrator.
            </p>
        </div>
        <div class="footer">
            <img src="https://alpha-med-care.com/images/logo.png" alt="Alpha Med Care Logo" style="max-width:120px; margin-bottom:10px;">
            <p>alpha med care GmbH &bull; www.alpha-med-care.com &bull; service@alpha-med-care.com</p>
            <p><a href="https://alpha-med-care.com/datenschutz">Datenschutz</a> &bull; <a href="https://alpha-med-care.com/impressum">Impressum</a></p>
        </div>
    </div>
</body>
</html>
HTML;
}