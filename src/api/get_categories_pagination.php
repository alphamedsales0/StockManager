<?php
// api/get_categories_pagination.php
header('Content-Type: application/json');
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

    $map = ['id' => 'id', 'name' => 'bezeichnung', 'value' => 'schluessel'];
    $sortColumn = $map[$sortBy] ?? 'id';
    $sortOrder  = ($sortOrder === 'asc') ? 'ASC' : 'DESC';

    $offset = ($page - 1) * $itemsPerPage;

    $where  = "WHERE 1=1";
    $params = [];
    if ($search !== '') {
        $where .= " AND (bezeichnung LIKE :search OR schluessel LIKE :search OR route LIKE :search)";
        $params['search'] = "%{$search}%";
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM kategorien $where");
    foreach ($params as $k => $v) $stmt->bindValue(":$k", $v);
    $stmt->execute();
    $total = (int)$stmt->fetchColumn();

    $sql = "SELECT
                id,
                bezeichnung AS name,
                schluessel  AS value,
                route,
                sortierung,
                uebergeordnet_id,
                ist_aktiv
            FROM kategorien
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