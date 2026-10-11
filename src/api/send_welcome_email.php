<?php
// api/send_welcome_email.php

error_reporting(E_ALL);
ini_set('display_errors', 0);   // ⚠️ 0 pour ne pas casser le JSON de auth.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// --- Journalisation (même fichier que send_verification_code) ---
function log_welcome($msg) {
    file_put_contents(
        __DIR__ . '/verification_log.txt',
        date('Y-m-d H:i:s') . ' [WELCOME] - ' . $msg . PHP_EOL,
        FILE_APPEND
    );
}

/**
 * Envoie l'email de bienvenue.
 *
 * @param array $data [
 *   'email', 'firstName', 'lastName',
 *   'firma', 'kundennummer', 'personType'
 * ]
 * @return array ['success' => bool, 'error' => string|null]
 */
function sendWelcomeEmail($data) {
    log_welcome('=== DEBUT sendWelcomeEmail ===');

    // --- Chargement de PHPMailer (mêmes chemins que send_verification_code.php) ---
    $possible_paths = [
        __DIR__ . '/phpmailer/src/',
        __DIR__ . '/../phpmailer/src/',
        __DIR__ . '/../../phpmailer/src/',
        '/homepages/40/d4299030961/htdocs/phpmailer/src/',
        '/homepages/40/d4299030961/htdocs/api/phpmailer/src/',
    ];

    $phpmailer_base = null;
    foreach ($possible_paths as $path) {
        if (is_dir($path) && file_exists($path . 'Exception.php')) {
            $phpmailer_base = $path;
            log_welcome("PHPMailer trouvé dans : $phpmailer_base");
            break;
        }
    }

    if (!$phpmailer_base) {
        log_welcome('ERREUR: PHPMailer introuvable');
        return ['success' => false, 'error' => 'PHPMailer introuvable'];
    }

    require_once $phpmailer_base . 'Exception.php';
    require_once $phpmailer_base . 'PHPMailer.php';
    require_once $phpmailer_base . 'SMTP.php';

    // --- Chargement de la config SMTP (mêmes chemins) ---
    $possible_config_paths = [
        __DIR__ . '/config.php',
        __DIR__ . '/../config.php',
        __DIR__ . '/../../config.php',
        '/homepages/40/d4299030961/htdocs/api/config.php',
        '/homepages/40/d4299030961/htdocs/config.php',
    ];

    $config_file = null;
    foreach ($possible_config_paths as $path) {
        if (file_exists($path)) {
            $config_file = $path;
            log_welcome("config.php trouvé dans : $config_file");
            break;
        }
    }

    if (!$config_file) {
        log_welcome('ERREUR: config.php introuvable');
        return ['success' => false, 'error' => 'config.php introuvable'];
    }

    $config = require $config_file;

    $smtpHost     = $config['smtp_host'];
    $smtpPort     = $config['smtp_port'];
    $smtpUsername = $config['smtp_username'];
    $smtpPassword = $config['smtp_password'];

    // --- Chargement du template ---
    require_once __DIR__ . '/email_templates.php';

    // --- Préparation de l'email ---
    $email      = $data['email'] ?? '';
    $firstName  = $data['firstName'] ?? '';
    $firma      = $data['firma'] ?? '';
    $personType = $data['personType'] ?? '';

    if (empty($email)) {
        log_welcome('ERREUR: email vide');
        return ['success' => false, 'error' => 'Email vide'];
    }

    // Sujet
    $subject = 'Willkommen bei alpha-med-care GmbH – Ihre Registrierung war erfolgreich';

    // Corps HTML via le template
    $htmlBody = welcomeEmailTemplate($data);

    // Corps texte (fallback)
    $salutation = $firstName ?: ($firma ?: 'Kunde');
    $textBody = "Hallo $salutation,\n\n"
              . "vielen Dank für Ihre Registrierung bei alpha-med-care GmbH.\n\n"
              . "Ihre Kundennummer: " . ($data['kundennummer'] ?? '') . "\n"
              . "Ihre E-Mail: $email\n"
              . "Kundentyp: $personType\n\n"
              . "Sie können sich ab sofort anmelden unter:\n"
              . "https://alpha-med-care.com/login\n\n"
              . "Mit freundlichen Grüßen,\n"
              . "Ihr alpha-med-care Team";

    // --- Envoi ---
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = $smtpHost;
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtpUsername;
        $mail->Password   = $smtpPassword;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $smtpPort;
        $mail->CharSet    = 'UTF-8';
        $mail->isHTML(true);

        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true
            ]
        ];

        $mail->setFrom($smtpUsername, 'alpha-med-care Service');
        $mail->addAddress($email);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = $textBody;

        if (!$mail->send()) {
            throw new Exception('PHPMailer send failed: ' . $mail->ErrorInfo);
        }

        log_welcome("Email envoyé à $email");
        log_welcome('=== FIN SUCCESS ===');
        return ['success' => true, 'error' => null];

    } catch (Exception $e) {
        log_welcome('Erreur PHPMailer: ' . $e->getMessage());
        log_welcome('=== FIN ERROR ===');
        return ['success' => false, 'error' => $e->getMessage()];

    } catch (Throwable $e) {
        log_welcome('Erreur fatale: ' . $e->getMessage());
        log_welcome('=== FIN ERROR ===');
        return ['success' => false, 'error' => $e->getMessage()];
    }
}