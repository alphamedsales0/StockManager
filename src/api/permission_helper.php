<?php
// api/permission_helper.php
// ============================================================
// Helper centralisé pour CORS + permissions
// ============================================================

/**
 * Applique les en-têtes CORS compatibles credentials.
 * À appeler en TOUT DÉBUT de chaque endpoint.
 */
function applyCors(): void {
    $allowed_origins = [
        'https://alpha-med-care.com',
        'https://www.alpha-med-care.com',
        'https://stckmng.alpha-med-care.com',   // ✅ SOUS-DOMAINE FRONT
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

    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit(0);
    }
}

/**
 * Démarre la session avec le BON nom + domain partagé.
 */
function ensureSession(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';

        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '.alpha-med-care.com',   // ✅ cross-subdomain
            'secure'   => $isSecure,
            'httponly' => true,
            'samesite' => $isSecure ? 'None' : 'Lax'
        ]);

        session_name('STOCKMANAGER');
        session_start();
    }
}

function currentUserRole(): ?string {
    ensureSession();
    return $_SESSION['user_role'] ?? null;
}

function currentUserName(): string {
    ensureSession();
    return $_SESSION['user_name'] ?? $_SESSION['user_email'] ?? 'System';
}

function requireRoles(array $allowedRoles): void {
    $role = currentUserRole();

    // Admin passe toujours
    if ($role === 'Admin') return;

    if (!$role || !in_array($role, $allowedRoles, true)) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error'   => 'Sie haben keine Berechtigung für diese Aktion.',
            'code'    => 'FORBIDDEN',
            'role'    => $role
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

function requireTicketWriteAccess(): void {
    requireRoles(['Admin', 'Office']);
}