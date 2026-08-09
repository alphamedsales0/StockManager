<?php
// employees_create.php
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

// PHPMailer einbinden (falls vorhanden)
$usePHPMailer = false;
if (file_exists(__DIR__ . '/phpmailer/src/PHPMailer.php')) {
    require __DIR__ . '/phpmailer/src/Exception.php';
    require __DIR__ . '/phpmailer/src/PHPMailer.php';
    require __DIR__ . '/phpmailer/src/SMTP.php';
    $usePHPMailer = true;
}

// Config laden (für SMTP)
$config = [];
if (file_exists(__DIR__ . '/config.php')) {
    $config = require __DIR__ . '/config.php';
}

// Eingabe (FormData) – employee als JSON-String
$rawInput = file_get_contents('php://input');
$employeeData = null;
if (isset($_POST['employee'])) {
    $employeeData = json_decode($_POST['employee'], true);
} else {
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

// 1. Benutzername generieren (erster Teil des Vornamens + . + Nachname)
$firstPart = strtok($vorname, ' ');
$username = $firstPart . '.' . $nachname;

// 2. Sicheres Passwort generieren (12 Zeichen)
function generatePassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+-=';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $password;
}
$plainPassword = generatePassword();

$pdo->beginTransaction();

try {
    // 3. User anlegen (name = Benutzername, email, password_hash)
    $hashed = password_hash($plainPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("
        INSERT INTO users (name, email, password_hash, role, is_active, created_at, updated_at)
        VALUES (?, ?, ?, 'employee', 1, NOW(), NOW())
    ");
    $stmt->execute([$username, $email, $hashed]);
    $benutzer_id = $pdo->lastInsertId();
    file_put_contents(__DIR__ . '/debug.log', "User inserted with ID $benutzer_id, username: $username\n", FILE_APPEND);

    // 4. Employee-Datensatz anlegen
    $stmt = $pdo->prepare("
        INSERT INTO employees (
            benutzer_id, mitarbeiter_nummer, vorname, nachname, telefon, mobil,
            position, abteilung, einstellungsdatum, geburtsdatum, gehalt,
            notfall_kontakt_name, notfall_kontakt_telefon, aktualisiert_am
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $mitarbeiter_nummer = empty($employeeData['mitarbeiter_nummer']) ? null : $employeeData['mitarbeiter_nummer'];
    $stmt->execute([
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
        empty($employeeData['notfall_kontakt_telefon']) ? null : $employeeData['notfall_kontakt_telefon']
    ]);
    $mitarbeiter_id = $pdo->lastInsertId();
    file_put_contents(__DIR__ . '/debug.log', "Employee inserted with ID $mitarbeiter_id\n", FILE_APPEND);

    // 5. Adresse (optional)
    $addr = $employeeData['adresse'] ?? null;
    if ($addr && !empty($addr['strasse']) && !empty($addr['hausnummer'])) {
        $stmt = $pdo->prepare("
            INSERT INTO employees_addresses (
                mitarbeiter_id, adresstyp, strasse, hausnummer, plz, stadt, land, ist_aktiv
            ) VALUES (?, 'primär', ?, ?, ?, ?, ?, 1)
        ");
        $stmt->execute([
            $mitarbeiter_id,
            $addr['strasse'],
            $addr['hausnummer'],
            $addr['plz'] ?? null,
            $addr['stadt'] ?? null,
            $addr['land'] ?? 'Deutschland'
        ]);
        file_put_contents(__DIR__ . '/debug.log', "Address inserted\n", FILE_APPEND);
    }

    $pdo->commit();

    // 6. E‑Mail mit Zugangsdaten senden (PHPMailer bevorzugt)
    $mailSent = false;
    $mailError = null;

    // HTML- und Text-Version der E-Mail
    $subject = 'Ihre Zugangsdaten für das Mitarbeiterportal';
    $htmlBody = buildCredentialsEmail($vorname, $nachname, $username, $plainPassword);
    $textBody = "Guten Tag $vorname $nachname,\n\n";
    $textBody .= "Ihr Mitarbeiterkonto wurde eingerichtet.\n";
    $textBody .= "Benutzername: $username\n";
    $textBody .= "Passwort: $plainPassword\n\n";
    $textBody .= "Bitte ändern Sie Ihr Passwort nach dem ersten Login.\n\n";
    $textBody .= "Mit freundlichen Grüßen\nIhr Team";

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

    // Fallback mit mail()
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

    if ($mailSent) {
        file_put_contents(__DIR__ . '/debug.log', "Mail sent to $email\n", FILE_APPEND);
    } else {
        file_put_contents(__DIR__ . '/debug.log', "Mail could not be sent to $email - " . ($mailError ?: 'unknown error') . "\n", FILE_APPEND);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Mitarbeiter angelegt, E‑Mail wurde versendet',
        'id' => $benutzer_id,
        'mail_sent' => $mailSent,
        'mail_error' => $mailError
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    file_put_contents(__DIR__ . '/debug.log', "PDO ERROR: " . $e->getMessage() . " - Code: " . $e->getCode() . "\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} catch (Throwable $e) {
    $pdo->rollBack();
    file_put_contents(__DIR__ . '/debug.log', "GENERAL ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

/**
 * Erstellt eine moderne HTML-E-Mail mit den Zugangsdaten
 */
function buildCredentialsEmail($vorname, $nachname, $username, $password) {
    $changeDate = date('d.m.Y H:i');
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
        @media only screen and (max-width: 480px) {
            .container { margin: 10px; border-radius: 8px; }
            .header { padding: 20px; }
            .content { padding: 20px; }
        }
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