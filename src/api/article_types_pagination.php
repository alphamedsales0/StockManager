<?php
// api/article_types_pagination.php
require_once __DIR__ . '/permission_helper.php';
applyCors();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/database_connect.php';

try {
    $page         = isset($_GET['page'])         ? max((int)$_GET['page'], 1)         : 1;
    $itemsPerPage = isset($_GET['itemsPerPage']) ? max((int)$_GET['itemsPerPage'], 1) : 10;
    $search       = isset($_GET['search'])       ? trim($_GET['search'])              : '';
    $sortBy       = $_GET['sortBy']    ?? 'id';
    $sortOrder    = strtolower($_GET['sortOrder'] ?? 'desc');

    // Mapping front → colonnes réelles
    $map = ['id' => 'id', 'name' => 'bezeichnung', 'value' => 'schluessel'];
    $sortColumn = $map[$sortBy] ?? 'id';
    $sortOrder  = ($sortOrder === 'asc') ? 'ASC' : 'DESC';

    $offset = ($page - 1) * $itemsPerPage;

    $where  = '';
    $params = [];
    if ($search !== '') {
        $where = "WHERE bezeichnung LIKE :search OR schluessel LIKE :search";
        $params['search'] = "%{$search}%";
    }

    // Total
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM artikeltypen $where");
    foreach ($params as $k => $v) $stmt->bindValue(":$k", $v);
    $stmt->execute();
    $total = (int)$stmt->fetchColumn();

    // Items (on renvoie name + value pour compatibilité front)
    $sql = "SELECT
                id,
                bezeichnung AS name,
                schluessel  AS value,
                sortierung,
                ist_aktiv
            FROM artikeltypen
            $where
            ORDER BY $sortColumn $sortOrder
            LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $k => $v) $stmt->bindValue(":$k", $v);
    $stmt->bindValue(':limit',  $itemsPerPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset,       PDO::PARAM_INT);
    $stmt->execute();

    echo json_encode([
        'success'      => true,
        'page'         => $page,
        'itemsPerPage' => $itemsPerPage,
        'total'        => $total,
        'items'        => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
}