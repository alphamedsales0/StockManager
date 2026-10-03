<?php
// api/delete_article_type.php
require_once __DIR__ . '/permission_helper.php';
applyCors();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/database_connect.php';

$data = json_decode(file_get_contents('php://input'), true);
$id   = (int)($data['id'] ?? 0);

if (!$id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID fehlt']);
    exit;
}

try {
    // Protection : refuser si utilisé par des articles
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM artikel a
        INNER JOIN artikeltypen at ON a.artikeltyp = at.schluessel
        WHERE at.id = ?
    ");
    $stmt->execute([$id]);
    if ((int)$stmt->fetchColumn() > 0) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'message' => 'Dieser Artikeltyp wird noch von Produkten verwendet und kann nicht gelöscht werden.'
        ]);
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM artikeltypen WHERE id = ?");
    $stmt->execute([$id]);

    echo json_encode(['success' => true, 'message' => 'Artikeltyp gelöscht.'], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB-Fehler: ' . $e->getMessage()]);
}