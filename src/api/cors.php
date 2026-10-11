<?php
// api/cors.php
// ============================================================
// CORS — compatible avec cookies de session (credentials)
// À inclure EN TOUT PREMIER dans chaque endpoint API
// ============================================================

$allowed_origins = [
    'https://alpha-med-care.com',
    'https://stckmng.alpha-med-care.com',
    'http://localhost:5173',
    'http://localhost:3000',
    'http://127.0.0.1:5173',
    'http://127.0.0.1:3000'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if ($origin !== '') {
    if (in_array($origin, $allowed_origins, true)) {
        // ✅ Origine autorisée + credentials
        header("Access-Control-Allow-Origin: $origin");
        header('Access-Control-Allow-Credentials: true');
        header('Vary: Origin');
    } else {
        // ❌ Origine inconnue → on refuse proprement
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error'   => 'Origin not allowed: ' . $origin
        ]);
        exit;
    }
}
// Si pas d'Origin (même-origine, curl, Postman) → on laisse passer

header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

// ✅ Répondre immédiatement aux requêtes OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}