<?php
// api/verify_code.php

// --- Configuration CORS ---
$allowed_origins = [
    'https://alpha-med-care.com',
    'http://localhost:5173',
    'http://localhost:3000',
    'http://127.0.0.1:5173',
    'http://127.0.0.1:3000'
];

if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins)) {
    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
    header('Access-Control-Allow-Credentials: true');
} else {
    header('Access-Control-Allow-Origin: *');
}
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

$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['verification_code']) || !isset($_SESSION['verification_code_expiry'])) {
    $response['message'] = 'Kein aktiver Verifizierungscode. Bitte fordern Sie einen neuen an.';
    echo json_encode($response);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$code = trim($input['code'] ?? '');

if (empty($code)) {
    $response['message'] = 'Bitte geben Sie den Verifizierungscode ein.';
    echo json_encode($response);
    exit;
}

// Vérifier expiration
if (time() > $_SESSION['verification_code_expiry']) {
    unset($_SESSION['verification_code']);
    unset($_SESSION['verification_code_expiry']);
    $response['message'] = 'Der Verifizierungscode ist abgelaufen. Bitte fordern Sie einen neuen an.';
    echo json_encode($response);
    exit;
}

// Vérifier le code
if ($code !== $_SESSION['verification_code']) {
    $response['message'] = 'Der eingegebene Code ist falsch.';
    echo json_encode($response);
    exit;
}

// Code valide
$_SESSION['verification_ok'] = true;
unset($_SESSION['verification_code']);
unset($_SESSION['verification_code_expiry']);

$response['success'] = true;
$response['message'] = 'Verifizierung erfolgreich. Sie können jetzt Ihr Passwort ändern.';
echo json_encode($response);