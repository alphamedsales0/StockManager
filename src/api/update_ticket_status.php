<?php
// update_ticket_status.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

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

if (!isset($pdo) || !$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

$rawInput = file_get_contents('php://input');
if (empty($rawInput)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Empty request body']);
    exit;
}

$input = json_decode($rawInput, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON: ' . json_last_error_msg()]);
    exit;
}

$ticketId        = $input['ticket_id'] ?? null;
$newStatus       = $input['status'] ?? null;
$notifyCustomer  = $input['notify_customer'] ?? false;
$changedBy       = $input['changed_by'] ?? 'Admin';

if (!$ticketId || !$newStatus) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ticket_id and status required']);
    exit;
}

$response = ['success' => false];

try {
    // ----- 1) QUELLE ANHAND DER ID SICHER ERMITTELN -----
    // Zuerst in form_submissions prüfen
    $stmt = $pdo->prepare("SELECT 1 FROM form_submissions WHERE id = ?");
    $stmt->execute([$ticketId]);
    $found = $stmt->fetchColumn();
    if ($found) {
        $source = 'form';
    } else {
        $stmt = $pdo->prepare("SELECT 1 FROM angebot_requests WHERE id = ?");
        $stmt->execute([$ticketId]);
        $found = $stmt->fetchColumn();
        if ($found) {
            $source = 'angebot';
        } else {
            throw new Exception('Ticket mit ID ' . $ticketId . ' nicht gefunden.');
        }
    }

    // Tabelle und Referenzfeld basierend auf Quelle wählen
    $table = ($source === 'angebot') ? 'angebot_requests' : 'form_submissions';
    $idField = 'id';
    $refField = ($source === 'angebot') ? 'reference' : 'reference_number';

    // Alten Status und Referenznummer holen
    $stmt = $pdo->prepare("SELECT status, $refField as ref FROM $table WHERE $idField = ?");
    $stmt->execute([$ticketId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        throw new Exception('Ticket nicht gefunden (widersprüchlich)');
    }
    $oldStatus = $row['status'];
    $referenceNumber = $row['ref'];

    // Status aktualisieren
    $updateSql = "UPDATE $table SET status = :status, last_updated_by = :changedBy";
    $columns = $pdo->query("SHOW COLUMNS FROM $table")->fetchAll(PDO::FETCH_COLUMN);
    if (in_array('updated_at', $columns)) {
        $updateSql .= ", updated_at = NOW()";
    }
    $updateSql .= " WHERE $idField = :id";
    $stmt = $pdo->prepare($updateSql);
    $stmt->execute([':status' => $newStatus, ':changedBy' => $changedBy, ':id' => $ticketId]);

    // Kommentar für Timeline
    if ($oldStatus !== $newStatus) {
        $statusTextOld = translateStatus($oldStatus);
        $statusTextNew = translateStatus($newStatus);
        $commentText = "Status geändert von '$statusTextOld' zu '$statusTextNew'";
        $stmtComment = $pdo->prepare("INSERT INTO comments (ticket_id, source, author, text, type) VALUES (?, ?, ?, ?, 'status')");
        $stmtComment->execute([$ticketId, $source, $changedBy, $commentText]);
    }

    $response['success'] = true;

    // ----- E-MAIL-BENACHRICHTIGUNG (nur bei Änderung und gewünscht) -----
    if ($notifyCustomer && $oldStatus !== $newStatus) {
        $customerEmail = null;
        $customerName = 'Kunde';

        // E-Mail-Adresse und Name aus der Datenbank holen (basierend auf $source)
        if ($source === 'angebot') {
            $stmt = $pdo->prepare("SELECT email, firstname, lastname FROM angebot_requests WHERE id = ?");
            $stmt->execute([$ticketId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $customerEmail = $row['email'];
                $customerName = trim($row['firstname'] . ' ' . $row['lastname']);
                if (empty($customerName)) $customerName = 'Kunde';
            }
        } else { // form
            $stmt = $pdo->prepare("SELECT customer_data FROM form_submissions WHERE id = ?");
            $stmt->execute([$ticketId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $customer = json_decode($row['customer_data'], true);
                $customerEmail = $customer['email'] ?? null;
                $customerName = trim(($customer['firstname'] ?? '') . ' ' . ($customer['lastname'] ?? ''));
                if (empty($customerName)) $customerName = 'Kunde';
            }
        }

        if (!empty($customerEmail)) {
            $statusText = translateStatus($newStatus);
            $ticketRef = ($source === 'angebot') ? 'Angebot' : 'Ticket';
            $subject = "Ihr $ticketRef (Ref-Nr.: $referenceNumber) wurde auf '$statusText' gesetzt";

            // HTML- und Text-E-Mail erstellen
            $htmlBody = buildHtmlEmail($customerName, $referenceNumber, $statusText, $source, $ticketRef);
            $textBody = "Guten Tag $customerName,\n\n";
            $textBody .= "der Status Ihres $ticketRef (Ref-Nr.: $referenceNumber) wurde geändert zu: $statusText.\n\n";
            $textBody .= "Sie können den aktuellen Stand unter folgendem Link einsehen:\n";
            $textBody .= "https://alpha-med-care.com/ticket?ref=$referenceNumber&source=$source\n\n";
            $textBody .= "Viele Grüße,\nIhr Support-Team";

            $mailSent = false;
            $mailError = null;

            // Versand mit PHPMailer (falls verfügbar)
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
                    $mail->addAddress($customerEmail);
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
                $mailSent = @mail($customerEmail, $subject, $htmlBody, $headers);
                if (!$mailSent) {
                    $headers = "From: service@alpha-med-care.com\r\n";
                    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
                    $mailSent = @mail($customerEmail, $subject, $textBody, $headers);
                }
            }

            if ($mailSent) {
                $response['notification_sent'] = true;
            } else {
                $response['mail_error'] = $mailError ?: 'E-Mail-Versand fehlgeschlagen (siehe Server-Log)';
                error_log("Mail failed for ticket $ticketId to $customerEmail");
            }
        } else {
            $response['mail_error'] = 'Keine E-Mail-Adresse für Kunden gefunden';
        }
    }

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

/**
 * Übersetzt den Status-Code in einen lesbaren Text
 */
function translateStatus($status) {
    $map = [
        'pending'     => 'In Bearbeitung',
        'in_progress' => 'In Prüfung',
        'completed'   => 'Abgeschlossen',
        'cancelled'   => 'Storniert'
    ];
    return $map[$status] ?? $status;
}

/**
 * Erstellt eine moderne HTML-E-Mail mit Inline-CSS
 */
function buildHtmlEmail($customerName, $referenceNumber, $statusText, $source, $ticketRef) {
    $statusColor = '#ff9800'; // orange für pending
    if ($statusText === 'In Prüfung')   $statusColor = '#2196f3';
    if ($statusText === 'Abgeschlossen') $statusColor = '#4caf50';
    if ($statusText === 'Storniert')     $statusColor = '#f44336';

    $link = "https://alpha-med-care.com/ticket?ref=$referenceNumber&source=$source";
    $changeDate = date('d.m.Y H:i');

    return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statusänderung</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; background-color: #f4f7fc; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 30px auto; background: #ffffff; border-radius: 12px; box-shadow: 0 8px 20px rgba(0,0,0,0.05); overflow: hidden; }
        .header { background: linear-gradient(135deg, #0f172a, #1e3c72); padding: 30px 25px; text-align: center; color: white; }
        .header h1 { margin: 0; font-size: 24px; font-weight: 600; letter-spacing: 0.5px; }
        .header p { margin: 8px 0 0; opacity: 0.8; font-size: 14px; }
        .content { padding: 30px 25px; }
        .greeting { font-size: 18px; font-weight: 600; color: #1e293b; margin-bottom: 10px; }
        .message { font-size: 16px; line-height: 1.6; color: #334155; margin-bottom: 20px; }
        .status-box { background: #f8fafc; border-left: 6px solid $statusColor; padding: 16px 20px; border-radius: 8px; margin: 20px 0; }
        .status-box .label { font-size: 14px; color: #64748b; }
        .status-box .status { font-size: 22px; font-weight: 700; color: $statusColor; margin-top: 4px; }
        .details { background: #f1f5f9; border-radius: 8px; padding: 15px 20px; margin: 20px 0; }
        .details table { width: 100%; border-collapse: collapse; }
        .details td { padding: 8px 0; border-bottom: 1px solid #e2e8f0; font-size: 14px; }
        .details td:last-child { text-align: right; font-weight: 500; color: #0f172a; }
        .details tr:last-child td { border-bottom: none; }
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
            <p>Ihr $ticketRef – Statusänderung</p>
        </div>
        <div class="content">
            <div class="greeting">Guten Tag $customerName,</div>
            <div class="message">
                der Status Ihres $ticketRef wurde aktualisiert. Sie können den aktuellen Stand jederzeit einsehen.
            </div>
            <div class="status-box">
                <div class="label">Neuer Status</div>
                <div class="status">$statusText</div>
            </div>
            <div class="details">
                <table>
                    <tr><td>Referenz-Nr.</td><td>$referenceNumber</td></tr>
                    <tr><td>Datum der Änderung</td><td>$changeDate</td></tr>
                    <tr><td>Geändert von</td><td>Support-Team</td></tr>
                </table>
            </div>
            <div style="text-align: center; margin: 25px 0 10px;">
                <a href="$link" class="btn">Zum Ticket-Detail</a>
            </div>
            <p style="font-size: 14px; color: #64748b; margin-top: 20px; text-align: center;">
                Bei Fragen stehen wir Ihnen gerne zur Verfügung.
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
?>