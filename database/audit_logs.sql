CREATE TABLE IF NOT EXISTS audit_logs (
    id          BIGINT AUTO_INCREMENT PRIMARY KEY,
    actor_id    BIGINT       NULL,
    action      VARCHAR(80)  NOT NULL,
    target_type VARCHAR(50)  NULL,
    target_id   BIGINT       NULL,
    ip          VARCHAR(45)  NULL,
    user_agent  VARCHAR(255) NULL,
    meta        JSON         NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_actor   (actor_id),
    INDEX idx_action  (action),
    INDEX idx_target  (target_type, target_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
