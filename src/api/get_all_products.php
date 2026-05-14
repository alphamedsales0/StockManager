<?php
// get_all_products.php
// API endpoint pour récupérer tous les produits avec leurs caractéristiques principales
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$host = 'db5018574434.hosting-data.io';
$db   = 'dbs14737411';
$user = 'dbu2173288';
$pass = 'cc4ef!NQfm4UAFs';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Requête principale avec jointures optionnelles pour récupérer des caractéristiques spécifiques
    $sql = "
        SELECT 
            a.id,
            a.name,
            a.brand,
            a.category,
            a.price,
            a.main_image,
            a.in_stock,
            a.is_new,
            a.best_seller,
            a.article_number,
            a.warranty_years,
            a.weight_capacity,
            a.description,
            -- caractéristiques pour les vélos (exercise_bikes)
            eb.resistance_type,
            eb.max_resistance,
            eb.console_features,
            -- caractéristiques pour les tapis de course (treadmills)
            t.motor_power,
            t.max_speed,
            t.max_inclination,
            t.display_type,
            t.has_touchscreen
        FROM articles a
        LEFT JOIN exercise_bikes eb ON a.id = eb.article_id
        LEFT JOIN treadmills t ON a.id = t.article_id
        ORDER BY a.id DESC
    ";
    
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll();
    
    $products = [];
    foreach ($rows as $row) {
        // Construire un tableau de caractéristiques (features) à partir des données disponibles
        $features = [];
        if (!empty($row['max_speed'])) $features[] = "Max. Geschwindigkeit: {$row['max_speed']} km/h";
        if (!empty($row['max_inclination'])) $features[] = "Max. Steigung: {$row['max_inclination']} %";
        if (!empty($row['motor_power'])) $features[] = "Motorleistung: {$row['motor_power']} PS";
        if (!empty($row['resistance_type'])) $features[] = "Widerstand: {$row['resistance_type']}";
        if (!empty($row['max_resistance'])) $features[] = "Max. Widerstand: {$row['max_resistance']} Level";
        if (!empty($row['console_features'])) $features[] = "Konsole: {$row['console_features']}";
        if (!empty($row['has_touchscreen'])) $features[] = "Touchscreen: " . ($row['has_touchscreen'] ? 'Ja' : 'Nein');
        if (!empty($row['weight_capacity'])) $features[] = "Max. Benutzergewicht: {$row['weight_capacity']} kg";
        if (!empty($row['warranty_years'])) $features[] = "Garantie: {$row['warranty_years']} Jahre";
        
        // Construction de l'objet produit (clés en camelCase)
        $products[] = [
            'id'             => (int)$row['id'],
            'name'           => $row['name'],
            'brand'          => $row['brand'],
            'category'       => $row['category'],
            'price'          => (float)$row['price'],
            'main_image'     => $row['main_image'],
            'inStock'        => (bool)$row['in_stock'],
            'isNew'          => (bool)$row['is_new'],
            'bestSeller'     => (bool)$row['best_seller'],
            'article_number' => $row['article_number'],
            'features'       => $features,               // tableau de chaînes pour affichage
            'warranty_years' => $row['warranty_years'] ? (int)$row['warranty_years'] : null,
            'weight_capacity'=> $row['weight_capacity'] ? (int)$row['weight_capacity'] : null,
            'description'    => $row['description'],
        ];
    }
    
    echo json_encode(['success' => true, 'products' => $products], JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>