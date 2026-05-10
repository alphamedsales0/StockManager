<?php
// api/stock_manager_products.php
// Gestion CORS dynamique (identique à votre exemple qui fonctionne)
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');
}

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    }
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    }
    exit(0);
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/database_connect.php';

// ------------------------------------------------------------
// 1. GET : récupérer tous les produits
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $pdo->query("SELECT * FROM articles ORDER BY id DESC");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $products], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ------------------------------------------------------------
// 2. POST : créer un nouveau produit (avec spécificités, images, livraison)
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input || !isset($input['article'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Données du produit manquantes']);
        exit;
    }

    $article = $input['article'];
    $specifics = $input['specifics'] ?? [];
    $shipping = $input['shipping'] ?? [];
    $images = $input['images'] ?? [];

    // Validation des champs obligatoires de la table `articles`
    $required = ['name', 'brand', 'category', 'price', 'main_image', 'article_type', 'article_number'];
    foreach ($required as $field) {
        if (empty($article[$field])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => "Champ requis manquant : $field"]);
            exit;
        }
    }

    try {
        $pdo->beginTransaction();

        // ----- Insertion dans la table `articles` -----
        $sql = "INSERT INTO articles (
                    name, brand, category, price, main_image, article_type, article_number,
                    color, warranty_years, weight_capacity, power_supply, application_area,
                    in_stock, is_new, best_seller, description
                ) VALUES (
                    :name, :brand, :category, :price, :main_image, :article_type, :article_number,
                    :color, :warranty_years, :weight_capacity, :power_supply, :application_area,
                    :in_stock, :is_new, :best_seller, :description
                )";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':name' => $article['name'],
            ':brand' => $article['brand'],
            ':category' => $article['category'],
            ':price' => $article['price'],
            ':main_image' => $article['main_image'],
            ':article_type' => $article['article_type'],
            ':article_number' => $article['article_number'],
            ':color' => $article['color'] ?? null,
            ':warranty_years' => $article['warranty_years'] ?? null,
            ':weight_capacity' => $article['weight_capacity'] ?? null,
            ':power_supply' => $article['power_supply'] ?? null,
            ':application_area' => $article['application_area'] ?? null,
            ':in_stock' => $article['in_stock'] ?? 1,
            ':is_new' => $article['is_new'] ?? 0,
            ':best_seller' => $article['best_seller'] ?? 0,
            ':description' => $article['description'] ?? null
        ]);

        $articleId = $pdo->lastInsertId();

        // ----- Insertion dans la table spécifique selon `article_type` -----
        if ($article['article_type'] === 'treadmill') {
            $sql = "INSERT INTO treadmills (
                        article_id, motor_power, max_speed, max_inclination, display_type,
                        training_programs, has_ekg, is_foldable, has_touchscreen, has_bluetooth,
                        has_heart_rate_monitor, has_wifi, has_speaker, power_range, display_info,
                        programs_info, comfort_features
                    ) VALUES (
                        :article_id, :motor_power, :max_speed, :max_inclination, :display_type,
                        :training_programs, :has_ekg, :is_foldable, :has_touchscreen, :has_bluetooth,
                        :has_heart_rate_monitor, :has_wifi, :has_speaker, :power_range, :display_info,
                        :programs_info, :comfort_features
                    )";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':article_id' => $articleId,
                ':motor_power' => $specifics['motor_power'] ?? null,
                ':max_speed' => $specifics['max_speed'] ?? null,
                ':max_inclination' => $specifics['max_inclination'] ?? null,
                ':display_type' => $specifics['display_type'] ?? null,
                ':training_programs' => $specifics['training_programs'] ?? null,
                ':has_ekg' => $specifics['has_ekg'] ?? 0,
                ':is_foldable' => $specifics['is_foldable'] ?? 0,
                ':has_touchscreen' => $specifics['has_touchscreen'] ?? 0,
                ':has_bluetooth' => $specifics['has_bluetooth'] ?? 0,
                ':has_heart_rate_monitor' => $specifics['has_heart_rate_monitor'] ?? 0,
                ':has_wifi' => $specifics['has_wifi'] ?? 0,
                ':has_speaker' => $specifics['has_speaker'] ?? 0,
                ':power_range' => $specifics['power_range'] ?? null,
                ':display_info' => $specifics['display_info'] ?? null,
                ':programs_info' => $specifics['programs_info'] ?? null,
                ':comfort_features' => $specifics['comfort_features'] ?? null
            ]);
        } elseif ($article['article_type'] === 'bike') {
            $sql = "INSERT INTO exercise_bikes (
                        article_id, resistance_type, max_resistance, pedal_type, seat_adjustment,
                        handlebar_adjustment, has_backrest, has_pedal_straps, console_features
                    ) VALUES (
                        :article_id, :resistance_type, :max_resistance, :pedal_type, :seat_adjustment,
                        :handlebar_adjustment, :has_backrest, :has_pedal_straps, :console_features
                    )";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':article_id' => $articleId,
                ':resistance_type' => $specifics['resistance_type'] ?? null,
                ':max_resistance' => $specifics['max_resistance'] ?? null,
                ':pedal_type' => $specifics['pedal_type'] ?? null,
                ':seat_adjustment' => $specifics['seat_adjustment'] ?? null,
                ':handlebar_adjustment' => $specifics['handlebar_adjustment'] ?? null,
                ':has_backrest' => $specifics['has_backrest'] ?? 0,
                ':has_pedal_straps' => $specifics['has_pedal_straps'] ?? 0,
                ':console_features' => $specifics['console_features'] ?? null
            ]);
        }
        // Vous pouvez ajouter d'autres types (elliptical_trainer, recumbent_bike, etc.) ici

        // ----- Insertion des images supplémentaires -----
        if (!empty($images)) {
            $sql = "INSERT INTO article_images (article_id, image_url, image_order, image_type, article_name)
                    VALUES (:article_id, :image_url, :image_order, :image_type, :article_name)";
            $stmt = $pdo->prepare($sql);
            foreach ($images as $image) {
                $stmt->execute([
                    ':article_id' => $articleId,
                    ':image_url' => $image['url'],
                    ':image_order' => $image['image_order'] ?? 1,
                    ':image_type' => $image['type'] ?? 'gallery',
                    ':article_name' => $image['article_name'] ?? $article['name']
                ]);
            }
        }

        // ----- Insertion des règles de livraison -----
        if (!empty($shipping)) {
            $sql = "INSERT INTO shipping_rules (
                        article_id, shipping_cost, free_shipping_threshold, shipping_method, estimated_delivery_days
                    ) VALUES (
                        :article_id, :shipping_cost, :free_shipping_threshold, :shipping_method, :estimated_delivery_days
                    )";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':article_id' => $articleId,
                ':shipping_cost' => (isset($shipping['shipping_cost']) && $shipping['shipping_cost'] !== '') ? (float)$shipping['shipping_cost'] : null,
                ':free_shipping_threshold' => (isset($shipping['free_shipping_threshold']) && $shipping['free_shipping_threshold'] !== '') ? (float)$shipping['free_shipping_threshold'] : null,
                ':shipping_method' => $shipping['shipping_method'] ?? null,
                ':estimated_delivery_days' => (isset($shipping['estimated_delivery_days']) && $shipping['estimated_delivery_days'] !== '') ? (int)$shipping['estimated_delivery_days'] : null
            ]);
        }

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Produit créé avec succès',
            'article_id' => $articleId,
            'article_number' => $article['article_number']
        ], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Erreur SQL : ' . $e->getMessage(),
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// Si la méthode HTTP n'est ni GET ni POST
http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Méthode non autorisée'], JSON_UNESCAPED_UNICODE);
?>