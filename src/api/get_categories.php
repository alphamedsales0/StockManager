<?php
// api/get_categories.php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require_once __DIR__ . '/database.php';

try {
    $parentSchluessel = $_GET['parent'] ?? null;   // ex: ?parent=training
    $onlyRoots        = isset($_GET['roots']) && $_GET['roots'] == '1';
    $withParentId     = isset($_GET['all']) && $_GET['all'] == '1';

    $sql = "SELECT
                k.id,
                k.schluessel   AS value,
                k.bezeichnung  AS name,
                k.route,
                k.uebergeordnet_id,
                k.sortierung
            FROM kategorien k
            WHERE k.ist_aktiv = 1";

    $params = [];

    // Filtre par parent (?parent=training)
    if ($parentSchluessel !== null && $parentSchluessel !== '') {
        $sql .= " AND k.uebergeordnet_id = (
                    SELECT id FROM kategorien WHERE schluessel = :parent LIMIT 1
                  )";
        $params[':parent'] = $parentSchluessel;
    }
    // Filtre racines uniquement (v-select "Kategorie principale")
    elseif ($onlyRoots) {
        $sql .= " AND k.uebergeordnet_id IS NULL";
    }

    $sql .= " ORDER BY k.sortierung ASC, k.bezeichnung ASC";

    $stmt = $dbh->prepare($sql);
    $stmt->execute($params);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'items' => $items], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'DB error: ' . $e->getMessage()]);
}