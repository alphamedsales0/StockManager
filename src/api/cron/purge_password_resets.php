<?php
// api/cron/purge_password_resets.php
// À exécuter toutes les heures via cron
// Usage : php /path/to/api/cron/purge_password_resets.php
//   ou : curl https://stckmng.alpha-med-care.com/api/cron/purge_password_resets.php?key=SECRET

// --- Protection par clé secrète (pour appel HTTP) ---
$cronKey = 'CHANGEZ_MOI_EN_LONG_RANDOM_STRING';
if (PHP_SAPI !== 'cli') {
    if (($_GET['key'] ?? '') !== $cronKey) {
        http_response_code(403);
        exit('Forbidden');
    }
}

require_once __DIR__ . '/../database_connect.php';
if (!$pdo instanceof PDO) {
    exit('DB unavailable');
}

$deleted = ['tokens' => 0, 'attempts' => 0];

// ============================================================
// 1. Purger les tokens expirés ou utilisés depuis > 7 jours
// ============================================================
try {
    $stmt = $pdo->prepare("
        DELETE FROM password_reset_tokens
        WHERE (expires_at < NOW() AND used_at IS NULL)
           OR (used_at IS NOT NULL AND used_at < (NOW() - INTERVAL 7 DAY))
    ");
    $stmt->execute();
    $deleted['tokens'] = $stmt->rowCount();
} catch (Throwable $e) {
    error_log('[purge_password_resets] tokens: ' . $e->getMessage());
}

// ============================================================
// 2. Purger les tentatives > 24h (garde 24h pour audit court)
// ============================================================
try {
    $stmt = $pdo->prepare("
        DELETE FROM password_reset_attempts
        WHERE created_at < (NOW() - INTERVAL 24 HOUR)
    ");
    $stmt->execute();
    $deleted['attempts'] = $stmt->rowCount();
} catch (Throwable $e) {
    error_log('[purge_password_resets] attempts: ' . $e->getMessage());
}

// ============================================================
// 3. Log
// ============================================================
error_log(sprintf(
    '[purge_password_resets] %s — tokens=%d, attempts=%d',
    date('Y-m-d H:i:s'),
    $deleted['tokens'],
    $deleted['attempts']
));

echo json_encode([
    'success' => true,
    'deleted' => $deleted,
    'timestamp' => date('c'),
]);