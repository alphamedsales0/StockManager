<?php
// api/verify_code_stock.php
declare(strict_types=1);

// --- CORS ---
$allowed_origins = [
    'https://alpha-med-care.com',
    'http://localhost:5173',
    'http://localhost:3000',
    'http://127.0.0.1:5173',
    'http://127.0.0.1:3000',
];

if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins, true)) {
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
    header('Access-Control-Allow-Credentials: true');
} else {
    header('Access-Control-Allow-Origin: *');
}
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// --- SESSION ---
$isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => $isSecure,
    'httponly' => true,
    'samesite' => $isSecure ? 'None' : 'Lax',
]);
session_name('STOCKMANAGER');
session_start();

// --- Journalisation ---
function log_message($msg) {
    file_put_contents(
        __DIR__ . '/stockmanager_log.txt',
        date('Y-m-d H:i:s') . ' - ' . $msg . PHP_EOL,
        FILE_APPEND
    );
}
log_message('=== DEBUT verify_code_stock.php ===');

$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['verification_code']) || !isset($_SESSION['verification_code_expiry'])) {
    log_message('ERREUR: aucun code en session.');
    $response['message'] = 'Kein aktiver Verifizierungscode. Bitte fordern Sie einen neuen an.';
    echo json_encode($response);
    exit;
}

$rawBody = file_get_contents('php://input');
$input   = json_decode($rawBody, true) ?: [];
$code    = trim((string) ($input['code'] ?? ''));

if ($code === '') {
    log_message('ERREUR: code vide.');
    $response['message'] = 'Bitte geben Sie den Verifizierungscode ein.';
    echo json_encode($response);
    exit;
}

if (time() > (int) $_SESSION['verification_code_expiry']) {
    log_message('ERREUR: code expiré.');
    unset($_SESSION['verification_code'], $_SESSION['verification_code_expiry']);
    $response['message'] = 'Der Verifizierungscode ist abgelaufen. Bitte fordern Sie einen neuen an.';
    echo json_encode($response);
    exit;
}

if (!hash_equals((string) $_SESSION['verification_code'], $code)) {
    log_message('ERREUR: code incorrect (saisi=' . $code . ').');
    $response['message'] = 'Der eingegebene Code ist falsch.';
    echo json_encode($response);
    exit;
}

$_SESSION['verification_ok'] = true;
unset($_SESSION['verification_code'], $_SESSION['verification_code_expiry']);

log_message('SUCCESS: code validé pour ' . ($_SESSION['user_email'] ?? 'inconnu'));

$response['success'] = true;
$response['message'] = 'Verifizierung erfolgreich. Sie können jetzt Ihr Passwort ändern.';
echo json_encode($response);

log_message('=== FIN verify_code_stock.php ===');