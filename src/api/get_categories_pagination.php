<?php
// ------------------------------------------------------
// CORS
// ------------------------------------------------------
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ------------------------------------------------------
// Connexion BDD
// ------------------------------------------------------
require_once __DIR__ . '/database_connect.php';

if (!isset($pdo) || $pdo === null) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Datenbankverbindung fehlgeschlagen']);
    exit;
}

// ------------------------------------------------------
// Paramètres
// ------------------------------------------------------
try {
    $page          = isset($_GET['page'])          ? max((int)$_GET['page'], 1) : 1;
    $itemsPerPage  = isset($_GET['itemsPerPage'])  ? max((int)$_GET['itemsPerPage'], 1) : 10;
    $search        = isset($_GET['search'])        ? trim($_GET['search']) : '';
    $sortBy        = isset($_GET['sortBy'])        ? $_GET['sortBy'] : 'id';
    $sortOrder     = isset($_GET['sortOrder'])     ? strtolower($_GET['sortOrder']) : 'desc';

    // Whitelist colonnes autorisées pour le tri
    $allowedColumns = ['id', 'name', 'value'];
    if (!in_array($sortBy, $allowedColumns)) {
        $sortBy = 'id';
    }
    $sortOrder = ($sortOrder === 'asc') ? 'asc' : 'desc';

    $offset = ($page - 1) * $itemsPerPage;

    // WHERE avec recherche (name ou value)
    $where = '';
    $params = [];
    if ($search !== '') {
        $where = "WHERE name LIKE :search OR value LIKE :search";
        $params['search'] = "%{$search}%";
    }

    // Total
    $sqlTotal = "SELECT COUNT(*) AS total FROM categories $where";
    $stmt = $pdo->prepare($sqlTotal);
    foreach ($params as $key => $val) {
        $stmt->bindValue(":$key", $val);
    }
    $stmt->execute();
    $total = (int)$stmt->fetchColumn();

    // Données paginées
    $sql = "SELECT id, name, value
            FROM categories
            $where
            ORDER BY $sortBy $sortOrder
            LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue(":$key", $val);
    }
    $stmt->bindValue(':limit',  $itemsPerPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset,       PDO::PARAM_INT);
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success"      => true,
        "page"         => $page,
        "itemsPerPage" => $itemsPerPage,
        "total"        => $total,
        "items"        => $items
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Datenbankfehler: " . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Allgemeiner Fehler: " . $e->getMessage()
    ]);
}