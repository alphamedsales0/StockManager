<?php
// api/permission_helper.php
// ============================================================
// Helper centralisé pour CORS + permissions
// ✅ Compatible localhost + production
// ✅ Insensible à la casse (admin/Admin/ADMIN)
// ============================================================

/**
 * Applique les en-têtes CORS compatibles credentials.
 * À appeler en TOUT DÉBUT de chaque endpoint.
 */
function applyCors(): void {
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

    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Content-Type: application/json; charset=utf-8');

    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        http_response_code(200);
        exit(0);
    }
}

/**
 * Démarre la session avec le BON nom + domain.
 * ✅ Compatible localhost ET production (comme auth_stock.php)
 */
function ensureSession(): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $origin_check = $_SERVER['HTTP_ORIGIN'] ?? '';

    // ✅ Détection localhost (comme auth_stock.php)
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
}

/**
 * Récupère le rôle courant en MINUSCULE
 */
function currentUserRole(): ?string {
    ensureSession();
    $role = $_SESSION['user_role'] ?? null;
    return $role ? strtolower(trim($role)) : null;
}

/**
 * Récupère le nom d'utilisateur courant
 */
function currentUserName(): string {
    ensureSession();
    return $_SESSION['user_name'] ?? $_SESSION['user_email'] ?? 'System';
}

/**
 * Requiert l'utilisateur authentifié (sinon 401)
 */
function requireAuth(): void {
    ensureSession();

    if (empty($_SESSION['user_id'])) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success'      => false,
            'error'        => 'Nicht authentifiziert',
            'code'         => 'UNAUTHORIZED',
            'session_keys' => array_keys($_SESSION)
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

/**
 * Requiert que l'utilisateur ait UN des rôles listés.
 * ✅ Admin passe TOUJOURS (comparaison en minuscule)
 * ✅ Insensible à la casse
 */
function requireRoles(array $allowedRoles): void {
    ensureSession();

    $role = currentUserRole();

    // ✅ Admin passe toujours (comparaison en minuscule)
    if ($role === 'admin') {
        return;
    }

    // ✅ Normalise les rôles autorisés en minuscule
    $allowedLower = array_map(fn($r) => strtolower(trim($r)), $allowedRoles);

    if (!$role || !in_array($role, $allowedLower, true)) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success'      => false,
            'error'        => 'Sie haben keine Berechtigung für diese Aktion.',
            'code'         => 'FORBIDDEN',
            'role'         => $role,
            'allowed'      => $allowedLower
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

/**
 * Requiert Admin uniquement
 */
function requireAdmin(): void {
    requireRoles(['admin']);
}

/**
 * Requiert Admin OU Office
 */
function requireAdminOrOffice(): void {
    requireRoles(['admin', 'office']);
}

/**
 * Requiert l'accès en écriture aux tickets
 */
function requireTicketWriteAccess(): void {
    requireRoles(['admin', 'office']);
}

/**
 * Vérifie si l'utilisateur a un rôle spécifique (insensible à la casse)
 */
function hasRole(string $role): bool {
    return currentUserRole() === strtolower(trim($role));
}

/**
 * Vérifie si l'utilisateur a l'un des rôles listés
 */
function hasAnyRole(array $roles): bool {
    $role = currentUserRole();
    if (!$role) return false;
    $allowedLower = array_map(fn($r) => strtolower(trim($r)), $roles);
    return in_array($role, $allowedLower, true);
}