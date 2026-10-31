<?php
// api/database_connect.php
$host_name = 'db5018574434.hosting-data.io';
$database  = 'dbs14737411';
$user_name = 'dbu2173288';
$password  = 'cc4ef!NQfm4UAFs';

try {
    $pdo = new PDO("mysql:host=$host_name;dbname=$database;charset=utf8mb4", $user_name, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    $pdo = null;
}

/**
 * Enregistre une action dans audit_logs.
 * Ne casse jamais le flux applicatif (try/catch interne).
 */
function audit_log($pdo, $actorId, $action, $targetType = null, $targetId = null, $meta = []) {
    if (!$pdo instanceof PDO) {
        error_log('[audit_log] PDO invalide.');
        return false;
    }

    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
    if ($ip !== null) {
        $ip = trim(explode(',', $ip)[0]);
        if (!filter_var($ip, FILTER_VALIDATE_IP)) $ip = null;
    }

    $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);

    try {
        $pdo->prepare("
            INSERT INTO audit_logs
                (actor_id, action, target_type, target_id, ip, user_agent, meta, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ")->execute([
            $actorId,
            $action,
            $targetType,
            $targetId,
            $ip,
            $ua,
            $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null,
        ]);
        return true;
    } catch (Throwable $e) {
        error_log('[audit_log] ' . $e->getMessage());
        return false;
    }
}