<?php
// api/send_verification_code.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Configuration CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// --- SESSION ---
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

// --- Journalisation ---
function log_message($msg) {
    file_put_contents(__DIR__ . '/verification_log.txt', date('Y-m-d H:i:s') . ' - ' . $msg . PHP_EOL, FILE_APPEND);
}
log_message('=== DEBUT send_verification_code.php ===');

// --- Recherche de PHPMailer dans plusieurs emplacements ---
$possible_paths = [
    __DIR__ . '/phpmailer/src/',                // même dossier que le script
    __DIR__ . '/../phpmailer/src/',              // un niveau au‑dessus
    __DIR__ . '/../../phpmailer/src/',           // deux niveaux au‑dessus
    '/homepages/40/d4299030961/htdocs/phpmailer/src/', // chemin absolu possible
    '/homepages/40/d4299030961/htdocs/api/phpmailer/src/', // autre possibilité
];

$phpmailer_base = null;
foreach ($possible_paths as $path) {
    if (is_dir($path) && file_exists($path . 'Exception.php')) {
        $phpmailer_base = $path;
        log_message("PHPMailer trouvé dans : $phpmailer_base");
        break;
    }
}

if (!$phpmailer_base) {
    log_message("ERREUR: PHPMailer introuvable. Chemins testés : " . implode(', ', $possible_paths));
    echo json_encode(['success' => false, 'message' => 'PHPMailer non trouvé. Voir logs pour les chemins testés.']);
    exit;
}

// Charger PHPMailer
require $phpmailer_base . 'Exception.php';
require $phpmailer_base . 'PHPMailer.php';
require $phpmailer_base . 'SMTP.php';
log_message('PHPMailer chargé avec succès');

// --- Recherche de config.php ---
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
        log_message("config.php trouvé dans : $config_file");
        break;
    }
}

if (!$config_file) {
    log_message("config.php introuvable. Chemins testés : " . implode(', ', $possible_config_paths));
    echo json_encode(['success' => false, 'message' => 'Fichier de configuration manquant. Voir logs.']);
    exit;
}

$config = require $config_file;

$smtpHost     = $config['smtp_host'];
$smtpPort     = $config['smtp_port'];
$smtpUsername = $config['smtp_username'];
$smtpPassword = $config['smtp_password'];
$serviceEmail = $config['service_email'];

// --- Récupération de l'email ---
$input = json_decode(file_get_contents('php://input'), true);
$emailFromBody = $input['email'] ?? null;

if (empty($_SESSION['user_email']) && !empty($emailFromBody)) {
    $_SESSION['user_email'] = $emailFromBody;
    log_message("Email récupéré du body: $emailFromBody");
}

if (empty($_SESSION['user_email'])) {
    log_message('ERREUR: Aucun email en session ou dans le body.');
    echo json_encode(['success' => false, 'message' => 'Nicht eingeloggt und keine E-Mail angegeben.']);
    exit;
}

$email = $_SESSION['user_email'];
log_message("Email utilisé: $email");

// --- Génération du code ---
$code = sprintf("%06d", random_int(0, 999999));
$_SESSION['verification_code'] = $code;
$_SESSION['verification_code_expiry'] = time() + 120;
$_SESSION['verification_ok'] = false;
log_message("Code généré: $code");

// --- Envoi de l'email ---
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
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ];


    $mail->setFrom($smtpUsername, 'alpha-med-care Service');
    $mail->addAddress($email);
    $mail->Subject = "🔐 Ihr Verifizierungscode – alpha-med-care";
    $mail->Body = "
    <html><body>
        <h2>Ihr Verifizierungscode</h2>
        <p style='font-size:28px;font-weight:bold;background:#f0f4ff;padding:15px;border-radius:8px;text-align:center;'>$code</p>
        <p>Dieser Code ist <strong>2 Minuten</strong> gültig.</p>
        <p>Wenn Sie diese Anfrage nicht gestellt haben, ignorieren Sie bitte diese E-Mail.</p>
    </body></html>
    ";
    $mail->AltBody = "Ihr Verifizierungscode: $code\nDieser Code ist 10 Minuten gültig.";

    if (!$mail->send()) {
        throw new Exception('PHPMailer send failed: ' . $mail->ErrorInfo);
    }

    log_message('Email envoyé à ' . $email);
    echo json_encode(['success' => true, 'message' => 'Verifizierungscode wurde an Ihre E-Mail gesendet.']);

} catch (Exception $e) {
    log_message('Erreur PHPMailer: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Fehler beim Senden der E-Mail. Bitte versuchen Sie es später erneut.']);
} catch (Throwable $e) {
    log_message('Erreur fatale: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ein interner Fehler ist aufgetreten.']);
}

log_message('=== FIN ===');