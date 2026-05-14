<?php
// get_product_details.php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$host_name = 'db5018574434.hosting-data.io';
$database = 'dbs14737411';
$user_name = 'dbu2173288';
$password = 'cc4ef!NQfm4UAFs';

try {
    $dbh = new PDO("mysql:host=$host_name; dbname=$database; charset=utf8mb4", $user_name, $password);
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $identifier = isset($_GET['id']) ? trim($_GET['id']) : null;
    $articleNumber = isset($_GET['article_number']) ? trim($_GET['article_number']) : null;

    if ($identifier && !$articleNumber && !is_numeric($identifier)) {
        $articleNumber = $identifier;
        $identifier = null;
    }

    if (!$identifier && !$articleNumber) {
        echo json_encode(['success' => false, 'error' => 'ID oder article_number erforderlich']);
        exit;
    }

    // 1. Récupération de l'article + shipping_rules
    $sql = "SELECT 
                a.id, a.name, a.brand, a.category, a.price, a.main_image,
                a.in_stock, a.is_new, a.best_seller, a.article_number,
                a.color, a.warranty_years, a.weight_capacity,
                a.power_supply, a.application_area, a.description,
                a.article_type,
                sr.shipping_cost, sr.free_shipping_threshold,
                sr.shipping_method, sr.estimated_delivery_days
            FROM articles a
            LEFT JOIN shipping_rules sr ON a.id = sr.article_id
            WHERE " . ($identifier ? "a.id = :id" : "a.article_number = :an");
    
    $stmt = $dbh->prepare($sql);
    if ($identifier) {
        $stmt->execute([':id' => (int)$identifier]);
    } else {
        $stmt->execute([':an' => $articleNumber]);
    }
    $article = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$article) {
        echo json_encode(['success' => false, 'error' => 'Produkt nicht gefunden']);
        exit;
    }

    // 2. Spécificités selon le type
    $specifics = [];
    if ($article['article_type'] === 'treadmill') {
        $stmt = $dbh->prepare("SELECT * FROM treadmills WHERE article_id = ?");
        $stmt->execute([$article['id']]);
        $specifics = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    } elseif ($article['article_type'] === 'bike') {
        $stmt = $dbh->prepare("SELECT * FROM exercise_bikes WHERE article_id = ?");
        $stmt->execute([$article['id']]);
        $specifics = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    // 3. Images (toutes, avec leur type)
    $stmt = $dbh->prepare("SELECT id, image_url, image_type, image_order FROM article_images WHERE article_id = ? ORDER BY image_order");
    $stmt->execute([$article['id']]);
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Construction de la réponse imbriquée (compatible avec EditProduct.vue)
    $response = [
        'success' => true,
        'product' => [
            'article' => [
                'name' => $article['name'],
                'brand' => $article['brand'],
                'category' => $article['category'],
                'price' => (float)$article['price'],
                'main_image' => $article['main_image'],
                'article_type' => $article['article_type'],
                'article_number' => $article['article_number'],
                'color' => $article['color'],
                'warranty_years' => $article['warranty_years'] ? (int)$article['warranty_years'] : null,
                'weight_capacity' => $article['weight_capacity'],
                'power_supply' => $article['power_supply'],
                'application_area' => $article['application_area'],
                'in_stock' => (bool)$article['in_stock'],
                'is_new' => (bool)$article['is_new'],
                'best_seller' => (bool)$article['best_seller'],
                'description' => $article['description']
            ],
            'specifics' => $specifics,
            'shipping' => [
                'shipping_cost' => $article['shipping_cost'] ? (float)$article['shipping_cost'] : null,
                'free_shipping_threshold' => $article['free_shipping_threshold'] ? (float)$article['free_shipping_threshold'] : null,
                'shipping_method' => $article['shipping_method'],
                'estimated_delivery_days' => $article['estimated_delivery_days'] ? (int)$article['estimated_delivery_days'] : null
            ],
            'images' => array_map(function($img) {
                return [
                    'id' => $img['id'],
                    'url' => $img['image_url'],
                    'type' => $img['image_type'], // 'main', 'gallery', 'detail'
                    'order' => $img['image_order']
                ];
            }, $images)
        ]
    ];

    echo json_encode($response, JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>