CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    source VARCHAR(20) NOT NULL DEFAULT 'form',
    author VARCHAR(100),
    text TEXT,
    type VARCHAR(20) DEFAULT 'comment',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ticket_source (ticket_id, source)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
