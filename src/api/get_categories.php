<?php
// ------------------------------------------------------
// CORS
// ------------------------------------------------------
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Allow-Headers: Content-Type");
    header("Access-Control-Allow-Methods: GET, OPTIONS");
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

header('Content-Type: application/json; charset=utf-8');

require_once "database_connect.php";

try {
    $stmt = $pdo->query("SELECT id, name, value FROM categories ORDER BY name ASC");
    echo json_encode([
        "success" => true,
        "items"   => $stmt->fetchAll()
    ], JSON_UNESCAPED_UNICODE);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Fehler beim Laden der Kategorien.",
        "error"   => $e->getMessage()
    ]);
}