<?php
// update_product.php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['product_id']) || !isset($input['article'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit;
}

$productId = (int)$input['product_id'];
$articleData = $input['article'];
$specifics = $input['specifics'] ?? [];
$shippingData = $input['shipping'] ?? [];
$imagesData = $input['images'] ?? [];

$host_name = 'db5018574434.hosting-data.io';
$database = 'dbs14737411';
$user_name = 'dbu2173288';
$password = 'cc4ef!NQfm4UAFs';

try {
    $dbh = new PDO("mysql:host=$host_name; dbname=$database; charset=utf8mb4", $user_name, $password);
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $dbh->beginTransaction();

    // 1. Mise à jour de la table articles
    $sql = "UPDATE articles SET
                name = :name,
                brand = :brand,
                category = :category,
                price = :price,
                main_image = :main_image,
                article_type = :article_type,
                article_number = :article_number,
                color = :color,
                warranty_years = :warranty_years,
                weight_capacity = :weight_capacity,
                power_supply = :power_supply,
                application_area = :application_area,
                in_stock = :in_stock,
                is_new = :is_new,
                best_seller = :best_seller,
                description = :description
            WHERE id = :id";
    
    $stmt = $dbh->prepare($sql);
    $stmt->execute([
        ':name' => $articleData['name'],
        ':brand' => $articleData['brand'],
        ':category' => $articleData['category'],
        ':price' => $articleData['price'],
        ':main_image' => $articleData['main_image'],
        ':article_type' => $articleData['article_type'],
        ':article_number' => $articleData['article_number'],
        ':color' => $articleData['color'] ?? null,
        ':warranty_years' => $articleData['warranty_years'] ?? null,
        ':weight_capacity' => $articleData['weight_capacity'] ?? null,
        ':power_supply' => $articleData['power_supply'] ?? null,
        ':application_area' => $articleData['application_area'] ?? null,
        ':in_stock' => $articleData['in_stock'] ? 1 : 0,
        ':is_new' => $articleData['is_new'] ? 1 : 0,
        ':best_seller' => $articleData['best_seller'] ? 1 : 0,
        ':description' => $articleData['description'] ?? null,
        ':id' => $productId
    ]);

    // 2. Mise à jour des spécificités (treadmill / bike)
    if ($articleData['article_type'] === 'treadmill') {
        $check = $dbh->prepare("SELECT id FROM treadmills WHERE article_id = ?");
        $check->execute([$productId]);
        if ($check->fetch()) {
            $sql = "UPDATE treadmills SET
                        motor_power = :motor_power,
                        max_speed = :max_speed,
                        max_inclination = :max_inclination,
                        display_type = :display_type,
                        training_programs = :training_programs,
                        has_ekg = :has_ekg,
                        is_foldable = :is_foldable,
                        has_touchscreen = :has_touchscreen,
                        has_bluetooth = :has_bluetooth,
                        has_heart_rate_monitor = :has_heart_rate_monitor,
                        has_wifi = :has_wifi,
                        has_speaker = :has_speaker,
                        power_range = :power_range,
                        display_info = :display_info,
                        programs_info = :programs_info,
                        comfort_features = :comfort_features
                    WHERE article_id = :article_id";
        } else {
            $sql = "INSERT INTO treadmills (
                        article_id, motor_power, max_speed, max_inclination,
                        display_type, training_programs, has_ekg, is_foldable,
                        has_touchscreen, has_bluetooth, has_heart_rate_monitor,
                        has_wifi, has_speaker, power_range, display_info,
                        programs_info, comfort_features
                    ) VALUES (
                        :article_id, :motor_power, :max_speed, :max_inclination,
                        :display_type, :training_programs, :has_ekg, :is_foldable,
                        :has_touchscreen, :has_bluetooth, :has_heart_rate_monitor,
                        :has_wifi, :has_speaker, :power_range, :display_info,
                        :programs_info, :comfort_features
                    )";
        }
        $stmt = $dbh->prepare($sql);
        $stmt->execute([
            ':article_id' => $productId,
            ':motor_power' => $specifics['motor_power'] ?? null,
            ':max_speed' => $specifics['max_speed'] ?? null,
            ':max_inclination' => $specifics['max_inclination'] ?? null,
            ':display_type' => $specifics['display_type'] ?? null,
            ':training_programs' => $specifics['training_programs'] ?? null,
            ':has_ekg' => isset($specifics['has_ekg']) ? (int)$specifics['has_ekg'] : 0,
            ':is_foldable' => isset($specifics['is_foldable']) ? (int)$specifics['is_foldable'] : 0,
            ':has_touchscreen' => isset($specifics['has_touchscreen']) ? (int)$specifics['has_touchscreen'] : 0,
            ':has_bluetooth' => isset($specifics['has_bluetooth']) ? (int)$specifics['has_bluetooth'] : 0,
            ':has_heart_rate_monitor' => isset($specifics['has_heart_rate_monitor']) ? (int)$specifics['has_heart_rate_monitor'] : 1,
            ':has_wifi' => isset($specifics['has_wifi']) ? (int)$specifics['has_wifi'] : 0,
            ':has_speaker' => isset($specifics['has_speaker']) ? (int)$specifics['has_speaker'] : 0,
            ':power_range' => $specifics['power_range'] ?? null,
            ':display_info' => $specifics['display_info'] ?? null,
            ':programs_info' => $specifics['programs_info'] ?? null,
            ':comfort_features' => $specifics['comfort_features'] ?? null
        ]);
    }
    elseif ($articleData['article_type'] === 'bike') {
        $check = $dbh->prepare("SELECT id FROM exercise_bikes WHERE article_id = ?");
        $check->execute([$productId]);
        if ($check->fetch()) {
            $sql = "UPDATE exercise_bikes SET
                        resistance_type = :resistance_type,
                        max_resistance = :max_resistance,
                        pedal_type = :pedal_type,
                        seat_adjustment = :seat_adjustment,
                        handlebar_adjustment = :handlebar_adjustment,
                        has_backrest = :has_backrest,
                        has_pedal_straps = :has_pedal_straps,
                        console_features = :console_features
                    WHERE article_id = :article_id";
        } else {
            $sql = "INSERT INTO exercise_bikes (
                        article_id, resistance_type, max_resistance, pedal_type,
                        seat_adjustment, handlebar_adjustment, has_backrest,
                        has_pedal_straps, console_features
                    ) VALUES (
                        :article_id, :resistance_type, :max_resistance, :pedal_type,
                        :seat_adjustment, :handlebar_adjustment, :has_backrest,
                        :has_pedal_straps, :console_features
                    )";
        }
        $stmt = $dbh->prepare($sql);
        $stmt->execute([
            ':article_id' => $productId,
            ':resistance_type' => $specifics['resistance_type'] ?? null,
            ':max_resistance' => $specifics['max_resistance'] ?? null,
            ':pedal_type' => $specifics['pedal_type'] ?? null,
            ':seat_adjustment' => $specifics['seat_adjustment'] ?? null,
            ':handlebar_adjustment' => $specifics['handlebar_adjustment'] ?? null,
            ':has_backrest' => isset($specifics['has_backrest']) ? (int)$specifics['has_backrest'] : 0,
            ':has_pedal_straps' => isset($specifics['has_pedal_straps']) ? (int)$specifics['has_pedal_straps'] : 1,
            ':console_features' => $specifics['console_features'] ?? null
        ]);
    }

    // 3. Mise à jour des règles de livraison (shipping_rules)
    $checkShip = $dbh->prepare("SELECT id FROM shipping_rules WHERE article_id = ?");
    $checkShip->execute([$productId]);
    if ($checkShip->fetch()) {
        $sql = "UPDATE shipping_rules SET
                    shipping_cost = :shipping_cost,
                    free_shipping_threshold = :free_shipping_threshold,
                    shipping_method = :shipping_method,
                    estimated_delivery_days = :estimated_delivery_days
                WHERE article_id = :article_id";
    } else {
        $sql = "INSERT INTO shipping_rules (
                    article_id, shipping_cost, free_shipping_threshold,
                    shipping_method, estimated_delivery_days
                ) VALUES (
                    :article_id, :shipping_cost, :free_shipping_threshold,
                    :shipping_method, :estimated_delivery_days
                )";
    }
    $stmt = $dbh->prepare($sql);
    $stmt->execute([
        ':article_id' => $productId,
        ':shipping_cost' => $shippingData['shipping_cost'] ?? null,
        ':free_shipping_threshold' => $shippingData['free_shipping_threshold'] ?? null,
        ':shipping_method' => $shippingData['shipping_method'] ?? null,
        ':estimated_delivery_days' => $shippingData['estimated_delivery_days'] ?? null
    ]);

    // 4. Gestion des images (article_images)
    // On récupère les IDs des images existantes à conserver
    $keepImageIds = [];
    foreach ($imagesData as $img) {
        if (!empty($img['id'])) {
            $keepImageIds[] = (int)$img['id'];
        }
    }
    
    if (!empty($keepImageIds)) {
        $placeholders = implode(',', array_fill(0, count($keepImageIds), '?'));
        $deleteStmt = $dbh->prepare("DELETE FROM article_images WHERE article_id = ? AND id NOT IN ($placeholders)");
        $deleteStmt->execute(array_merge([$productId], $keepImageIds));
    } else {
        $deleteStmt = $dbh->prepare("DELETE FROM article_images WHERE article_id = ?");
        $deleteStmt->execute([$productId]);
    }
    
    // Mise à jour / insertion des images
    $order = 1;
    foreach ($imagesData as $img) {
        $url = $img['url'];
        $type = $img['type']; // 'main', 'gallery', 'detail'
        
        if (!empty($img['id'])) {
            $stmt = $dbh->prepare("UPDATE article_images SET image_url = ?, image_type = ?, image_order = ? WHERE id = ? AND article_id = ?");
            $stmt->execute([$url, $type, $order, $img['id'], $productId]);
        } else {
            // Récupérer l'article_name depuis la table articles
            $nameStmt = $dbh->prepare("SELECT name FROM articles WHERE id = ?");
            $nameStmt->execute([$productId]);
            $articleName = $nameStmt->fetchColumn();
            
            $stmt = $dbh->prepare("INSERT INTO article_images (article_id, image_url, image_type, image_order, article_name) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$productId, $url, $type, $order, $articleName]);
        }
        $order++;
    }

    $dbh->commit();
    echo json_encode(['success' => true, 'message' => 'Produkt erfolgreich aktualisiert']);

} catch (PDOException $e) {
    $dbh->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>