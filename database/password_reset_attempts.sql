-- ============================================================
-- Table de rate limiting pour les demandes de reset password
-- ============================================================
CREATE TABLE IF NOT EXISTS `password_reset_attempts` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ip`         VARCHAR(45)     NOT NULL COMMENT 'IPv4 ou IPv6',
    `email`      VARCHAR(190)    DEFAULT NULL COMMENT 'email demandé (peut être NULL si absent)',
    `mode`       ENUM('public','admin') NOT NULL DEFAULT 'public',
    `success`    TINYINT(1)      NOT NULL DEFAULT 1,
    `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    KEY `idx_ip_created`    (`ip`, `created_at`),
    KEY `idx_email_created` (`email`, `created_at`),
    KEY `idx_created`       (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
