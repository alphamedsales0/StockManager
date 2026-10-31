-- migrations/create_password_reset_tokens.sql
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id            BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id       BIGINT       NOT NULL,
    token_hash    CHAR(64)     NOT NULL,           -- SHA256 hex du token
    requested_by  BIGINT       NULL,               -- admin qui a déclenché (NULL = self-service)
    ip_request    VARCHAR(45)  NULL,
    user_agent    VARCHAR(255) NULL,
    expires_at    DATETIME     NOT NULL,
    used_at       DATETIME     NULL,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_token_hash (token_hash),
    INDEX idx_user_id (user_id),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
