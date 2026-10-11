<?php
// api/change_password.php

// --- CORS ---
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

require_once 'database.php';

$input = json_decode(file_get_contents('php://input'), true);
$response = ['success' => false, 'message' => ''];

// --- Déterminer le mode ---
$isVerified = isset($_SESSION['verification_ok']) && $_SESSION['verification_ok'] === true;
$isLoggedIn = isset($_SESSION['kunde_id']);

if (!$isLoggedIn && !$isVerified) {
    $response['message'] = 'Authentifizierung erforderlich.';
    echo json_encode($response);
    exit;
}

// --- Récupérer le kunde_id ---
if ($isLoggedIn) {
    $kundeId = $_SESSION['kunde_id'];
} else if ($isVerified) {
    // Mode reset : on utilise l'email stocké en session
    if (!isset($_SESSION['user_email']) || empty($_SESSION['user_email'])) {
        $response['message'] = 'E-Mail nicht in Session.';
        echo json_encode($response);
        exit;
    }
    $email = $_SESSION['user_email'];
    $stmt = $dbh->prepare("SELECT kunde_id FROM kunden_authentifizierung WHERE email = ?");
    $stmt->execute([$email]);
    $row = $stmt->fetch();
    if (!$row) {
        $response['message'] = 'Benutzer nicht gefunden.';
        echo json_encode($response);
        exit;
    }
    $kundeId = $row['kunde_id'];
}

$current = $input['currentPassword'] ?? '';
$new = $input['newPassword'] ?? '';

// --- Vérifier l'ancien mot de passe (seulement si connecté ET pas en mode reset) ---
if ($isLoggedIn && !$isVerified) {
    if (empty($current)) {
        $response['message'] = 'Aktuelles Passwort erforderlich.';
        echo json_encode($response);
        exit;
    }
    // Récupérer le hash actuel
    $stmt = $dbh->prepare("SELECT passwort_hash FROM kunden_authentifizierung WHERE kunde_id = ?");
    $stmt->execute([$kundeId]);
    $row = $stmt->fetch();
    if (!$row || !password_verify($current, $row['passwort_hash'])) {
        $response['message'] = 'Das aktuelle Passwort ist falsch.';
        echo json_encode($response);
        exit;
    }
}

// --- Valider le nouveau mot de passe ---
if (strlen($new) < 8) {
    $response['message'] = 'Das neue Passwort muss mindestens 8 Zeichen lang sein.';
    echo json_encode($response);
    exit;
}

// --- Mettre à jour ---
$newHash = password_hash($new, PASSWORD_DEFAULT);
$stmt = $dbh->prepare("UPDATE kunden_authentifizierung SET passwort_hash = ? WHERE kunde_id = ?");
if ($stmt->execute([$newHash, $kundeId])) {
    // Nettoyer les flags de session en mode reset
    unset($_SESSION['verification_ok']);
    unset($_SESSION['verification_code']);
    unset($_SESSION['verification_code_expiry']);
    $response['success'] = true;
    $response['message'] = 'Passwort erfolgreich geändert.';
} else {
    $response['message'] = 'Fehler beim Speichern des Passworts.';
}

echo json_encode($response);