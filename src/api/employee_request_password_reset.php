<?php
// api/employee_request_password_reset.php
// Déclenché par un admin/office → envoie un magic link à l'employé
// ⚠️ Ne pas confondre avec les endpoints du site partagé

use PHPMailer\PHPMailer\PHPMailer;

// ============================================================
// CORS — compatible avec cookies cross-origin
// ============================================================
$allowed_origins = [
    'https://alpha-med-care.com',
    'https://www.alpha-med-care.com',
    'https://stckmng.alpha-med-care.com',
    'http://localhost:5173',
    'http://localhost:3000',
    'http://127.0.0.1:5173',
    'http://127.0.0.1:3000',
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
    header('Vary: Origin');
}
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// ============================================================
// SESSION — identique à auth_stock.php (même cookie STOCKMANAGER)
// ============================================================
$isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
$host = $_SERVER['HTTP_HOST'] ?? '';
$origin_check = $_SERVER['HTTP_ORIGIN'] ?? '';

$isLocalhost = (
    strpos($host, 'localhost') !== false ||
    strpos($host, '127.0.0.1') !== false ||
    strpos($origin_check, 'localhost') !== false ||
    strpos($origin_check, '127.0.0.1') !== false
);

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => $isLocalhost ? '' : '.alpha-med-care.com',
    'secure'   => $isLocalhost ? false : $isSecure,
    'httponly' => true,
    'samesite' => $isLocalhost ? 'Lax' : ($isSecure ? 'None' : 'Lax'),
]);

session_name('STOCKMANAGER');
session_start();

// ============================================================
// DB
// ============================================================
require_once __DIR__ . '/database_connect.php';
if (!$pdo instanceof PDO) {
    http_response_code(500);
    exit(json_encode(['success' => false, 'message' => 'Datenbank nicht verfügbar.']));
}

// ============================================================
// AUTH
// ============================================================
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['success' => false, 'message' => 'Nicht authentifiziert.']));
}

// ============================================================
// RÔLE admin/office uniquement
// ============================================================
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([(int)$_SESSION['user_id']]);
$callerRole = strtolower($stmt->fetchColumn() ?: '');
if (!in_array($callerRole, ['admin', 'office'], true)) {
    http_response_code(403);
    exit(json_encode(['success' => false, 'message' => 'Keine Berechtigung.']));
}

// ============================================================
// BODY
// ============================================================
$input = json_decode(file_get_contents('php://input'), true) ?: [];
$targetUserId = (int)($input['target_user_id'] ?? 0);
if ($targetUserId <= 0) {
    exit(json_encode(['success' => false, 'message' => 'target_user_id fehlt.']));
}

// ============================================================
// EMPLOYÉ CIBLE
// ============================================================
$stmt = $pdo->prepare("
    SELECT u.id, u.email, u.username, u.is_active,
           e.vorname, e.nachname
    FROM users u
    LEFT JOIN employees e ON e.benutzer_id = u.id
    WHERE u.id = ? LIMIT 1
");
$stmt->execute([$targetUserId]);
$target = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$target) {
    exit(json_encode(['success' => false, 'message' => 'Benutzer nicht gefunden.']));
}
if (empty($target['is_active'])) {
    exit(json_encode(['success' => false, 'message' => 'Benutzerkonto ist inaktiv.']));
}
if (!filter_var($target['email'], FILTER_VALIDATE_EMAIL)) {
    exit(json_encode(['success' => false, 'message' => 'Benutzer hat keine gültige E-Mail.']));
}

// ============================================================
// INVALIDER ANCIENS TOKENS
// ============================================================
$pdo->prepare("
    UPDATE password_reset_tokens
    SET used_at = NOW()
    WHERE user_id = ? AND used_at IS NULL AND expires_at > NOW()
")->execute([$targetUserId]);

// ============================================================
// GÉNÉRER TOKEN
// ============================================================
$token     = bin2hex(random_bytes(32));
$tokenHash = hash('sha256', $token);
$expiresAt = (new DateTime('+30 minutes'))->format('Y-m-d H:i:s');
$ip        = $_SERVER['REMOTE_ADDR'] ?? null;
$ua        = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);

$stmt = $pdo->prepare("
    INSERT INTO password_reset_tokens
        (user_id, token_hash, requested_by, ip_request, user_agent, expires_at)
    VALUES (?, ?, ?, ?, ?, ?)
");
$stmt->execute([
    $targetUserId,
    $tokenHash,
    (int)$_SESSION['user_id'],
    $ip,
    $ua,
    $expiresAt,
]);

// ============================================================
// PHPMailer
// ============================================================
$possible_paths = [
    __DIR__ . '/phpmailer/src/',
    __DIR__ . '/../phpmailer/src/',
    __DIR__ . '/../../phpmailer/src/',
    '/homepages/40/d4299030961/htdocs/phpmailer/src/',
];
$phpmailer_base = null;
foreach ($possible_paths as $p) {
    if (is_dir($p) && file_exists($p . 'Exception.php')) {
        $phpmailer_base = $p;
        break;
    }
}
if (!$phpmailer_base) {
    exit(json_encode(['success' => false, 'message' => 'PHPMailer introuvable.']));
}
require $phpmailer_base . 'Exception.php';
require $phpmailer_base . 'PHPMailer.php';
require $phpmailer_base . 'SMTP.php';

// ============================================================
// CONFIG SMTP
// ============================================================
$config_paths = [
    __DIR__ . '/config.php',
    __DIR__ . '/../config.php',
    __DIR__ . '/../../config.php',
];
$config_file = null;
foreach ($config_paths as $p) {
    if (file_exists($p)) {
        $config_file = $p;
        break;
    }
}
if (!$config_file) {
    exit(json_encode(['success' => false, 'message' => 'config.php fehlt.']));
}
$config = require $config_file;

// ============================================================
// LIEN DE RESET
// ============================================================
$frontendUrl = 'https://stckmng.alpha-med-care.com';
$resetLink   = $frontendUrl . '/password-reset?token=' . urlencode($token);

$fullName = trim(($target['vorname'] ?? '') . ' ' . ($target['nachname'] ?? ''));
if ($fullName === '') $fullName = $target['username'];

// ============================================================
// ENVOI EMAIL
// ============================================================
try {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = $config['smtp_host'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $config['smtp_username'];
    $mail->Password   = $config['smtp_password'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = $config['smtp_port'];
    $mail->CharSet    = 'UTF-8';
    $mail->isHTML(true);
    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true,
        ],
    ];

    $mail->setFrom($config['smtp_username'], 'alpha-med-care Stock-Manager');
    $mail->addAddress($target['email'], $fullName);
    $mail->Subject = '🔐 Passwort zurücksetzen – alpha-med-care Stock-Manager';

    $safeName = htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8');
    $safeLink = htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8');

    $mail->Body = "
    <html><body style='font-family:Arial,sans-serif;max-width:600px;margin:auto;'>
      <h2>Passwort zurücksetzen – alpha-med-care Stock-Manager</h2>
      <p>Hallo <strong>$safeName</strong>,</p>
      <p>Ein Administrator hat eine Passwort-Zurücksetzung für Ihr Konto angefordert.</p>
      <p>Klicken Sie auf den folgenden Link, um ein neues Passwort zu setzen :</p>
      <p style='text-align:center;margin:30px 0;'>
        <a href='$safeLink'
           style='display:inline-block;padding:14px 28px;background:#2563eb;color:#fff;
                  text-decoration:none;border-radius:8px;font-weight:bold;'>
          Passwort jetzt zurücksetzen
        </a>
      </p>
      <p style='color:#64748b;font-size:13px;'>
        Dieser Link ist <strong>30 Minuten</strong> gültig und kann nur einmal verwendet werden.<br>
        Falls Sie diese Anfrage nicht erwartet haben, ignorieren Sie diese E-Mail.
      </p>
      <p style='color:#94a3b8;font-size:11px;margin-top:30px;'>
        alpha-med-care Stock-Manager
      </p>
    </body></html>";

    $mail->AltBody = "Hallo $fullName,\n\nPasswort zurücksetzen (alpha-med-care Stock-Manager):\n$resetLink\n\nGültig 30 Minuten.";

    $mail->send();

    // --- Audit log ---
    audit_log(
        $pdo,
        (int)$_SESSION['user_id'],
        'password_reset_requested',
        'user',
        $targetUserId,
        [
            'target_email' => $target['email'],
            'expires_at'   => $expiresAt,
        ]
    );

    echo json_encode([
        'success' => true,
        'message' => 'E-Mail mit Zurücksetzungslink wurde an ' . $target['email'] . ' gesendet.',
    ]);
} catch (Throwable $e) {
    error_log('[password_reset_request] ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'E-Mail konnte nicht gesendet werden.']);
}