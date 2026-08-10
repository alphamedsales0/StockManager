-- Bankverbindung
CREATE TABLE employee_bank_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mitarbeiter_id INT NOT NULL,
    kontoinhaber VARCHAR(100) NOT NULL,
    iban VARCHAR(34) NOT NULL,
    bic VARCHAR(11),
    bankname VARCHAR(100),
    ist_aktiv BOOLEAN DEFAULT 0,
    bemerkung TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mitarbeiter_id) REFERENCES employees(id) ON DELETE CASCADE
);
