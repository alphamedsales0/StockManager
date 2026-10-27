<?php
// api/auth_stock.php

error_log("==== auth_stock.php called ====");

// ============================================================
// SESSION — EN PREMIER (avant tout autre code)
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

// ✅ FORCER le nom de session AVANT tout (contre session.auto_start)
session_name('STOCKMANAGER');

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => $isLocalhost ? '' : '.alpha-med-care.com',
    'secure'   => $isLocalhost ? false : $isSecure,
    'httponly' => true,
    'samesite' => $isLocalhost ? 'Lax' : ($isSecure ? 'None' : 'Lax')
]);

session_start();

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

header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json');

// ✅ Répondre IMMÉDIATEMENT au preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// ============================================================
// DATABASE
// ============================================================
require_once __DIR__ . '/database_connect.php';
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Datenbankverbindung fehlgeschlagen.']);
    exit;
}

// ============================================================
// RÔLES AUTORISÉS (pas de Gast)
// ============================================================
const ALLOWED_ROLES = ['Admin', 'Office', 'Technician'];

// ============================================================
// GÉNÉRATEUR D'UUID v4
// ============================================================
function generateUuidV4(): string {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $_GET['action'] ?? '';
$response = ['success' => false, 'message' => ''];

try {
    // ============================================================
    // 1. LOGIN (email OU username + password)
    // ============================================================
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'login') {
        $identifier = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';

        error_log("Login attempt with identifier: $identifier");

        if (empty($identifier) || empty($password)) {
            throw new Exception('E-Mail und Passwort erforderlich.');
        }

        $stmt = $pdo->prepare("
            SELECT id, uid, name, username, email, password_hash, role, is_active
            FROM users
            WHERE email = ? OR username = ?
            LIMIT 1
        ");
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            error_log("User not found for identifier: $identifier");
            throw new Exception('E-Mail oder Passwort falsch.');
        }

        if ((int)$user['is_active'] !== 1) {
            throw new Exception('Konto deaktiviert.');
        }

        if (!password_verify($password, $user['password_hash'])) {
            error_log("Password verification failed for: $identifier");
            throw new Exception('E-Mail oder Passwort falsch.');
        }

        if (!in_array($user['role'], ALLOWED_ROLES, true)) {
            error_log("Unauthorized role attempt: {$user['role']}");
            throw new Exception('Ungültige Rolle. Zugriff verweigert.');
        }

        $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);

        // ✅ Régénère l'ID de session (sécurité)
        session_regenerate_id(true);

        $_SESSION['user_id']    = (int)$user['id'];
        $_SESSION['user_uid']   = $user['uid'];
        $_SESSION['user_name']  = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role']  = $user['role'];

        $response['success'] = true;
        $response['message'] = 'Login erfolgreich.';
        $response['user'] = [
            'id'       => (int)$user['id'],
            'uid'      => $user['uid'],
            'name'     => $user['name'],
            'username' => $user['username'],
            'email'    => $user['email'],
            'role'     => $user['role']
        ];
    }

    // ============================================================
    // 2. GET USER (restauration de session)
    // ============================================================
    elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'user') {
        if (empty($_SESSION['user_id'])) {
            throw new Exception('Nicht eingeloggt.');
        }

        $userId = (int)$_SESSION['user_id'];
        $stmt = $pdo->prepare("
            SELECT id, uid, name, username, email, role, last_login, created_at
            FROM users
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            throw new Exception('Benutzer nicht gefunden.');
        }

        if (!in_array($user['role'], ALLOWED_ROLES, true)) {
            session_destroy();
            throw new Exception('Ungültige Rolle.');
        }

        $response['success'] = true;
        $response['user'] = [
            'id'         => (int)$user['id'],
            'uid'        => $user['uid'],
            'name'       => $user['name'],
            'username'   => $user['username'],
            'email'      => $user['email'],
            'role'       => $user['role'],
            'last_login' => $user['last_login'],
            'created_at' => $user['created_at']
        ];
    }

    // ============================================================
    // 3. LOGOUT
    // ============================================================
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'logout') {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        session_destroy();

        $response['success'] = true;
        $response['message'] = 'Logout erfolgreich.';
    }

    // ============================================================
    // 4. REGISTER (Admin uniquement)
    // ============================================================
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'register') {
        $name     = trim($input['name'] ?? '');
        $username = trim($input['username'] ?? '');
        $email    = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';
        $role     = trim($input['role'] ?? 'Technician');

        if (empty($name) || empty($username) || empty($email) || empty($password)) {
            throw new Exception('Name, Username, E-Mail und Passwort sind Pflichtfelder.');
        }
        if (strlen($password) < 8) {
            throw new Exception('Passwort muss mindestens 8 Zeichen lang sein.');
        }
        if (!in_array($role, ALLOWED_ROLES, true)) {
            throw new Exception('Ungültige Rolle.');
        }

        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        if ($stmt->fetch()) {
            throw new Exception('E-Mail oder Username bereits vergeben.');
        }

        $uid = generateUuidV4();
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            INSERT INTO users (uid, name, username, email, password_hash, role, is_active, created_at)
            VALUES (?, ?, ?, ?, ?, ?, 1, NOW())
        ");
        $stmt->execute([$uid, $name, $username, $email, $passwordHash, $role]);

        $newId = (int)$pdo->lastInsertId();

        session_regenerate_id(true);

        $_SESSION['user_id']    = $newId;
        $_SESSION['user_uid']   = $uid;
        $_SESSION['user_name']  = $name;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_role']  = $role;

        $response['success'] = true;
        $response['message'] = 'Registrierung erfolgreich.';
        $response['user'] = [
            'id'       => $newId,
            'uid'      => $uid,
            'name'     => $name,
            'username' => $username,
            'email'    => $email,
            'role'     => $role
        ];
    }

    else {
        throw new Exception('Ungültige Aktion.');
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    error_log("Auth API Error: " . $e->getMessage());
}

http_response_code(200);
echo json_encode($response, JSON_UNESCAPED_UNICODE);