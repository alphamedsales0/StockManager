-- --------------------------------------------------------
-- Tabelle: notifications (Systembenachrichtigungen)
-- --------------------------------------------------------
DROP TABLE IF EXISTS notifications;
CREATE TABLE notifications (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL COMMENT 'Empfänger (Benutzer-ID)',
    type VARCHAR(50) NOT NULL COMMENT 'Typ: info, warning, success, error',
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    link VARCHAR(255) NULL COMMENT 'Optionaler Link (z.B. zu einem Ticket)',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_is_read (is_read),
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabelle: messages (private Nachrichten zwischen Benutzern)
-- --------------------------------------------------------
DROP TABLE IF EXISTS messages;
CREATE TABLE messages (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    sender_id INT(11) NOT NULL,
    receiver_id INT(11) NOT NULL,
    subject VARCHAR(255) NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    parent_id INT(11) NULL COMMENT 'Für Antworten (Thread)',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sender_receiver (sender_id, receiver_id),
    INDEX idx_receiver_read (receiver_id, is_read),
    CONSTRAINT fk_messages_sender FOREIGN KEY (sender_id)
        REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_messages_receiver FOREIGN KEY (receiver_id)
        REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_messages_parent FOREIGN KEY (parent_id)
        REFERENCES messages(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
