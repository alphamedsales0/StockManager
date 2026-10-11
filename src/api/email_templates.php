<?php
// api/email_templates.php

/**
 * Template HTML de confirmation d'inscription.
 *
 * @param array $data [
 *   'firstName', 'lastName', 'email',
 *   'firma', 'kundennummer', 'personType'
 * ]
 * @return string HTML
 */
function welcomeEmailTemplate($data) {
    $firstName  = htmlspecialchars($data['firstName'] ?? '');
    $lastName   = htmlspecialchars($data['lastName'] ?? '');
    $firma      = htmlspecialchars($data['firma'] ?? '');
    $email      = htmlspecialchars($data['email'] ?? '');
    $kundennr   = htmlspecialchars($data['kundennummer'] ?? '');
    $personType = $data['personType'] ?? '';

    // Salutation adaptée
    if ($personType === 'Gewerbekunde' && $firma) {
        $greeting = "Sehr geehrte Damen und Herren von <strong>{$firma}</strong>";
    } elseif ($firstName) {
        $greeting = "Hallo <strong>{$firstName} {$lastName}</strong>";
    } else {
        $greeting = "Hallo";
    }

    $year = date('Y');

    return <<<HTML
<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Willkommen bei alpha-med-care GmbH</title>
</head>
<body style="margin:0;padding:0;font-family:'Helvetica Neue',Arial,sans-serif;background:#f4f6fa;color:#333;">
  <div style="max-width:600px;margin:0 auto;background:#ffffff;">

    <!-- HEADER -->
    <div style="background:linear-gradient(135deg,#1976d2,#42a5f5);padding:40px 30px;text-align:center;">
      <h1 style="margin:0;color:#ffffff;font-size:26px;font-weight:700;letter-spacing:-0.5px;">
        Willkommen bei alpha-med-care GmbH
      </h1>
      <p style="margin:8px 0 0;color:rgba(255,255,255,0.9);font-size:14px;">
        Ihre Registrierung war erfolgreich
      </p>
    </div>

    <!-- CONTENT -->
    <div style="padding:40px 30px;line-height:1.6;color:#333;">

      <h2 style="color:#1976d2;font-size:20px;margin-top:0;">{$greeting},</h2>

      <p style="margin:12px 0;font-size:15px;">
        vielen Dank für Ihre Registrierung bei <strong>alpha-med-care GmbH</strong>.
        Ihr Konto wurde erfolgreich erstellt und Sie können sich ab sofort anmelden.
      </p>

      <!-- INFO BOX -->
      <div style="background:#f0f7ff;border-left:4px solid #1976d2;padding:16px 20px;border-radius:6px;margin:20px 0;">
        <div style="margin:6px 0;font-size:14px;">
          <span style="color:#64748b;display:inline-block;min-width:130px;">E-Mail:</span>
          <strong style="color:#1976d2;">{$email}</strong>
        </div>
        <div style="margin:6px 0;font-size:14px;">
          <span style="color:#64748b;display:inline-block;min-width:130px;">Kundennummer:</span>
          <strong style="color:#1976d2;">{$kundennr}</strong>
        </div>
        <div style="margin:6px 0;font-size:14px;">
          <span style="color:#64748b;display:inline-block;min-width:130px;">Kundentyp:</span>
          <strong style="color:#1976d2;">{$personType}</strong>
        </div>
      </div>

      <p style="margin:12px 0;font-size:15px;">
        <strong>Bewahren Sie Ihre Kundennummer gut auf</strong> — Sie benötigen sie bei
        Rückfragen, Bestellungen oder für den Kundenservice.
      </p>

      <!-- BOUTON -->
      <p style="text-align:center;margin:30px 0;">
        <a href="https://alpha-med-care.com/login"
           style="display:inline-block;background:#1976d2;color:#ffffff;text-decoration:none;padding:14px 28px;border-radius:8px;font-weight:600;">
          Jetzt anmelden →
        </a>
      </p>

      <h2 style="color:#1976d2;font-size:20px;">Was können Sie jetzt tun?</h2>

      <div style="margin:24px 0;">
        <div style="padding:12px 0;border-bottom:1px solid #eef2f7;font-size:14px;">
          <span style="color:#10b981;font-weight:bold;margin-right:8px;">✓</span>
          Ihr vollständiges Profil vervollständigen
        </div>
        <div style="padding:12px 0;border-bottom:1px solid #eef2f7;font-size:14px;">
          <span style="color:#10b981;font-weight:bold;margin-right:8px;">✓</span>
          Adressen und Kontaktdaten hinzufügen
        </div>
        <div style="padding:12px 0;border-bottom:1px solid #eef2f7;font-size:14px;">
          <span style="color:#10b981;font-weight:bold;margin-right:8px;">✓</span>
          Produkte in Ihrem Konto speichern
        </div>
        <div style="padding:12px 0;font-size:14px;">
          <span style="color:#10b981;font-weight:bold;margin-right:8px;">✓</span>
          Bestellungen und Anfragen verwalten
        </div>
      </div>

      <p style="margin:12px 0;font-size:15px;">
        Bei Fragen steht Ihnen unser Kundenservice jederzeit gerne zur Verfügung:
      </p>

      <p style="margin:12px 0;font-size:14px;">
        📧 <a href="mailto:service@alpha-med-care.com" style="color:#1976d2;text-decoration:none;">service@alpha-med-care.com</a><br>
        🌐 <a href="https://alpha-med-care.com" style="color:#1976d2;text-decoration:none;">alpha-med-care.com</a>
      </p>

      <p style="margin-top:30px;font-size:15px;">
        Mit freundlichen Grüßen,<br>
        <strong>Ihr alpha-med-care Team</strong>
      </p>

    </div>

    <!-- FOOTER -->
    <div style="background:#f8fafc;padding:24px 30px;text-align:center;font-size:12px;color:#94a3b8;border-top:1px solid #e2e8f0;">
      <p style="margin:0 0 8px;">
        Diese E-Mail wurde automatisch generiert. Bitte antworten Sie nicht direkt darauf.
      </p>
      <p style="margin:0;">
        © {$year} alpha-med-care ·
        <a href="https://alpha-med-care.com" style="color:#1976d2;text-decoration:none;">alpha-med-care.com</a>
      </p>
    </div>

  </div>
</body>
</html>
HTML;
}