<?php
// employees_delete.php
// Supprime un employé et toutes ses données liées
// ✅ Accepte ?uid=... (employees.uid) OU ?id=... (users.id)
// ✅ Contrôle de permission : Admin + Office uniquement
// ✅ Suppression en cascade : tables enfants + employees + users
// ✅ Nettoyage du dossier /uploads/employees/{uid}/

// ============================================================
// CORS + SESSION (identique à auth_stock.php)
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

header('Access-Control-Allow-Methods: DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// ============================================================
// SESSION — mêmes paramètres que auth_stock.php
// ============================================================
$isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '.alpha-med-care.com',
    'secure'   => $isSecure,
    'httponly' => true,
    'samesite' => $isSecure ? 'None' : 'Lax'
]);

session_name('STOCKMANAGER');
session_start();

// ============================================================
// 🔓 CONTRÔLE DE PERMISSION
// ============================================================
$ALLOWED_ROLES = ['Admin', 'Office'];

$currentRole = $_SESSION['user_role'] ?? null;
$currentUserId = $_SESSION['user_id'] ?? null;

if (!$currentUserId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Nicht eingeloggt.']);
    exit;
}

if (!in_array($currentRole, $ALLOWED_ROLES, true)) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error'   => 'Zugriff verweigert. Nur Admin oder Office dürfen Mitarbeiter löschen.'
    ]);
    exit;
}

// ============================================================
// DATABASE
// ============================================================
require_once __DIR__ . '/database_connect.php';
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Datenbankverbindung fehlgeschlagen.']);
    exit;
}

$uid = $_GET['uid'] ?? null;
$id  = $_GET['id']  ?? 0;

if (!$uid && !$id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'UID oder ID fehlt']);
    exit;
}

try {
    $pdo->beginTransaction();

    if ($uid) {
        $stmt = $pdo->prepare("SELECT benutzer_id FROM employees WHERE uid = ?");
        $stmt->execute([$uid]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            $pdo->rollBack();
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Mitarbeiter nicht gefunden']);
            exit;
        }
        $benutzerId = (int)$row['benutzer_id'];
    } else {
        $benutzerId = (int)$id;
        $stmt = $pdo->prepare("SELECT uid FROM employees WHERE benutzer_id = ?");
        $stmt->execute([$benutzerId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $uid = $row['uid'] ?? null;
    }

    if ($uid) {
        $childTables = [
            'employees_addresses',
            'employee_bank_accounts',
            'employee_qualifications',
            'employee_documents',
            'mitarbeiter_versicherungen',
        ];
        foreach ($childTables as $table) {
            $stmt = $pdo->prepare("DELETE FROM {$table} WHERE mitarbeiter_uid = ?");
            $stmt->execute([$uid]);
        }

        $stmt = $pdo->prepare("DELETE FROM employees WHERE uid = ?");
        $stmt->execute([$uid]);
    }

    if ($benutzerId) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'employee'");
        $stmt->execute([$benutzerId]);
    }

    $pdo->commit();

    if ($uid && !empty($_SERVER['DOCUMENT_ROOT'])) {
        $dir = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/uploads/employees/' . $uid;
        if (is_dir($dir)) {
            try {
                $it = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::CHILD_FIRST
                );
                foreach ($it as $f) {
                    $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
                }
                @rmdir($dir);
            } catch (Throwable $e) {
                // ignore filesystem errors
            }
        }
    }

    echo json_encode(['success' => true, 'message' => 'Mitarbeiter gelöscht']);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}