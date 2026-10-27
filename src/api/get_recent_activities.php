<?php
// api/get_recent_activities.php
// Version avec mix forcé : au moins 1 activité par type
// ✅ Session compatible localhost ET production

error_log("==== get_recent_activities.php called ====");

// ============================================================
// CORS
// ============================================================
$allowed_origins = [
    'https://alpha-med-care.com',
    'https://www.alpha-med-care.com',
    'https://stckmng.alpha-med-care.com',
    'http://localhost:5173',
    'http://localhost:3000',
    'http://127.0.0.1:5173',
    'http://127.0.0.1:3000'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowed_origins, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
    header('Vary: Origin');
} else {
    header('Access-Control-Allow-Origin: *');
}

header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// ============================================================
// SESSION — compatible localhost ET production
// ============================================================
$isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
$host = $_SERVER['HTTP_HOST'] ?? '';
$origin_check = $_SERVER['HTTP_ORIGIN'] ?? '';

$isLocalhost = (
    strpos($host, 'localhost') !== false ||
    strpos($host, '127.0.0.1') !== false ||
    strpos($origin_check, 'localhost') !== false ||
    strpos($origin_check, '127.0.0.1') !== false
);

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => $isLocalhost ? '' : '.alpha-med-care.com',
    'secure'   => $isLocalhost ? false : $isSecure,
    'httponly' => true,
    'samesite' => $isLocalhost ? 'Lax' : ($isSecure ? 'None' : 'Lax')
]);

session_name('STOCKMANAGER');
session_start();

// ============================================================
// AUTH
// ============================================================
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Non authentifié',
        'debug' => [
            'session_id' => session_id(),
            'session_name' => session_name(),
            'session_keys' => array_keys($_SESSION),
            'host' => $host,
            'origin' => $origin_check,
            'cookie' => $_COOKIE,
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$userRole = $_SESSION['user_role'] ?? '';

const ALLOWED_ROLES = ['Admin', 'Office', 'Technician'];
if (!in_array($userRole, ALLOWED_ROLES, true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Rôle non autorisé']);
    exit;
}

// ============================================================
// DB
// ============================================================
require_once __DIR__ . '/database_connect.php';
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'DB-Verbindung fehlgeschlagen.']);
    exit;
}

$limit = isset($_GET['limit']) ? max(1, min(50, (int)$_GET['limit'])) : 10;
$perTypeLimit = 5;

try {
    $bucket = [
        'product_created'  => [],
        'ticket_created'   => [],
        'ticket_resolved'  => [],
        'customer_created' => [],
        'order_created'    => [],
        'employee_created' => [],
    ];

    // ============================================================
    // 1. Produits
    // ============================================================
    try {
        $stmt = $pdo->prepare("
            SELECT id, bezeichnung, erstellt_am
            FROM artikel
            WHERE erstellt_am IS NOT NULL
            ORDER BY erstellt_am DESC
            LIMIT :lim
        ");
        $stmt->bindValue(':lim', $perTypeLimit, PDO::PARAM_INT);
        $stmt->execute();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $bucket['product_created'][] = [
                'icon' => 'mdi-plus-box',
                'title' => 'Neues Produkt: ' . ($row['bezeichnung'] ?: '—'),
                'time' => $row['erstellt_am'],
                'color' => 'success',
                'type' => 'product_created',
                'link' => '/products/view/' . $row['id'],
            ];
        }
    } catch (Exception $e) {
        error_log("Activities: produits error: " . $e->getMessage());
    }

    // ============================================================
    // 2. Tickets
    // ============================================================
    try {
        $stmt = $pdo->prepare("
            SELECT id, titel, status, erstellt_am
            FROM tickets
            WHERE erstellt_am IS NOT NULL
            ORDER BY erstellt_am DESC
            LIMIT :lim
        ");
        $stmt->bindValue(':lim', $perTypeLimit, PDO::PARAM_INT);
        $stmt->execute();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $isResolved = in_array($row['status'], ['closed', 'resolved', 'done', 'erledigt'], true);
            $type = $isResolved ? 'ticket_resolved' : 'ticket_created';
            $bucket[$type][] = [
                'icon' => $isResolved ? 'mdi-check-circle' : 'mdi-ticket',
                'title' => ($isResolved ? 'Ticket gelöst: ' : 'Neues Ticket: ') . ($row['titel'] ?: '—'),
                'time' => $row['erstellt_am'],
                'color' => $isResolved ? 'success' : 'info',
                'type' => $type,
                'link' => '/tickets/' . $row['id'],
            ];
        }
    } catch (Exception $e) {
        error_log("Activities: tickets error: " . $e->getMessage());
    }

    // ============================================================
    // 3. Clients
    // ============================================================
    try {
        $stmt = $pdo->prepare("
            SELECT id, vorname, nachname, firmenname, erstellt_am
            FROM kunden
            WHERE erstellt_am IS NOT NULL
            ORDER BY erstellt_am DESC
            LIMIT :lim
        ");
        $stmt->bindValue(':lim', $perTypeLimit, PDO::PARAM_INT);
        $stmt->execute();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $name = $row['firmenname']
                ?: trim(($row['vorname'] ?? '') . ' ' . ($row['nachname'] ?? ''));
            $bucket['customer_created'][] = [
                'icon' => 'mdi-account-plus',
                'title' => 'Neuer Kunde: ' . ($name ?: '—'),
                'time' => $row['erstellt_am'],
                'color' => 'primary',
                'type' => 'customer_created',
                'link' => '/customers/' . $row['id'],
            ];
        }
    } catch (Exception $e) {
        error_log("Activities: kunden error: " . $e->getMessage());
    }

    // ============================================================
    // 4. Commandes
    // ============================================================
    try {
        $stmt = $pdo->prepare("
            SELECT id, bestellnummer, erstellt_am
            FROM bestellungen
            WHERE erstellt_am IS NOT NULL
            ORDER BY erstellt_am DESC
            LIMIT :lim
        ");
        $stmt->bindValue(':lim', $perTypeLimit, PDO::PARAM_INT);
        $stmt->execute();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $bucket['order_created'][] = [
                'icon' => 'mdi-file-document',
                'title' => 'Bestellung #' . ($row['bestellnummer'] ?: $row['id']) . ' erstellt',
                'time' => $row['erstellt_am'],
                'color' => 'warning',
                'type' => 'order_created',
                'link' => '/orders/' . $row['id'],
            ];
        }
    } catch (Exception $e) {
        error_log("Activities: bestellungen error: " . $e->getMessage());
    }

    // ============================================================
    // 5. Employés
    // ============================================================
    try {
        $stmt = $pdo->prepare("
            SELECT id, vorname, nachname, erstellt_am
            FROM mitarbeiter
            WHERE erstellt_am IS NOT NULL
            ORDER BY erstellt_am DESC
            LIMIT :lim
        ");
        $stmt->bindValue(':lim', $perTypeLimit, PDO::PARAM_INT);
        $stmt->execute();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $name = trim(($row['vorname'] ?? '') . ' ' . ($row['nachname'] ?? ''));
            $bucket['employee_created'][] = [
                'icon' => 'mdi-account-tie',
                'title' => 'Neuer Mitarbeiter: ' . ($name ?: '—'),
                'time' => $row['erstellt_am'],
                'color' => 'deep-purple',
                'type' => 'employee_created',
                'link' => '/employees/' . $row['id'],
            ];
        }
    } catch (Exception $e) {
        error_log("Activities: mitarbeiter error: " . $e->getMessage());
    }

    // ============================================================
    // MIX FORCÉ : 1 activité par type d'abord
    // ============================================================
    $mixed = [];
    foreach ($bucket as $type => $items) {
        if (!empty($items)) {
            $mixed[] = $items[0];
            array_shift($bucket[$type]);
        }
    }

    $remaining = [];
    foreach ($bucket as $type => $items) {
        foreach ($items as $item) {
            $remaining[] = $item;
        }
    }
    usort($remaining, fn($a, $b) => strtotime($b['time']) - strtotime($a['time']));

    while (count($mixed) < $limit && !empty($remaining)) {
        $mixed[] = array_shift($remaining);
    }

    usort($mixed, fn($a, $b) => strtotime($b['time']) - strtotime($a['time']));

    $activities = $mixed;

    // ============================================================
    // FLAG "new" (< 24h)
    // ============================================================
    $now = time();
    foreach ($activities as &$a) {
        $a['new'] = ($now - strtotime($a['time'])) < 86400;
    }
    unset($a);

    echo json_encode([
        'success' => true,
        'activities' => $activities,
        'count' => count($activities),
        'generated_at' => date('c'),
        'user' => [
            'id' => $userId,
            'role' => $userRole
        ]
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'DB error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}