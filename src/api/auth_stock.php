<?php
// api/auth_stock.php
error_log("==== auth_stock.php called ====");

// --- CORS ---
$allowed_origins = [
    'https://alpha-med-care.com',
    'http://localhost:5173',
    'http://localhost:3000',
    'http://127.0.0.1:5173',
    'http://127.0.0.1:3000'
];

if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins)) {
    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
    header('Access-Control-Allow-Credentials: true');
} else {
    header('Access-Control-Allow-Origin: *');
}
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// --- Session ---
$isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
$sameSite = $isSecure ? 'None' : 'Lax';
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $isSecure,
    'httponly' => true,
    'samesite' => $sameSite
]);
session_name('STOCKMANAGER');
session_start();

// --- Database ---
require_once __DIR__ . '/database_connect.php';
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Datenbankverbindung fehlgeschlagen.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$response = ['success' => false, 'message' => ''];

try {
    // --- LOGIN ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['action'] ?? '') === 'login') {
        $email = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';
        error_log("Login attempt with email: $email");

        if (empty($email) || empty($password)) {
            throw new Exception('E-Mail und Passwort erforderlich.');
        }

        $stmt = $pdo->prepare("SELECT id, name, email, password_hash, role, is_active FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        error_log("User found: " . print_r($user, true));

        if (!$user) {
            throw new Exception('Benutzer nicht gefunden.');
        }
        if ((int)$user['is_active'] !== 1) {
            throw new Exception('Konto deaktiviert.');
        }
        if (!password_verify($password, $user['password_hash'])) {
            error_log("Password verification failed for email: $email");
            throw new Exception('Passwort falsch.');
        }

        // Mettre à jour last_login
        $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);

        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];

        $response['success'] = true;
        $response['message'] = 'Login erfolgreich.';
        $response['user'] = [
            'id' => (int)$user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role']
        ];
    }

    // --- GET USER (session) ---
    elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'user') {
        if (empty($_SESSION['user_id'])) {
            throw new Exception('Nicht eingeloggt.');
        }
        $userId = (int)$_SESSION['user_id'];
        $stmt = $pdo->prepare("SELECT id, name, email, role, last_login, created_at FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            throw new Exception('Benutzer nicht gefunden.');
        }
        $response['success'] = true;
        $response['user'] = [
            'id' => (int)$user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'last_login' => $user['last_login'],
            'created_at' => $user['created_at']
        ];
    }

    // --- LOGOUT ---
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['action'] ?? '') === 'logout') {
        session_destroy();
        $response['success'] = true;
        $response['message'] = 'Logout erfolgreich.';
    }

    // --- REGISTER (optionnel) ---
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['action'] ?? '') === 'register') {
        $name = trim($input['name'] ?? '');
        $email = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';
        $role = trim($input['role'] ?? 'user');

        if (empty($name) || empty($email) || empty($password)) {
            throw new Exception('Name, E-Mail und Passwort sind Pflichtfelder.');
        }
        if (strlen($password) < 8) {
            throw new Exception('Passwort muss mindestens 8 Zeichen lang sein.');
        }
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            throw new Exception('Diese E-Mail ist bereits registriert.');
        }
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$name, $email, $passwordHash, $role]);

        $newId = (int)$pdo->lastInsertId();
        $_SESSION['user_id'] = $newId;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_role'] = $role;

        $response['success'] = true;
        $response['message'] = 'Registrierung erfolgreich.';
        $response['user'] = [
            'id' => $newId,
            'name' => $name,
            'email' => $email,
            'role' => $role
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
echo json_encode($response);