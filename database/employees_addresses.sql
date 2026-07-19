-- Table employees_addresses (idem)
CREATE TABLE employees_addresses (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    mitarbeiter_id INT(11) NOT NULL,
    adresstyp VARCHAR(20) DEFAULT 'primär',
    strasse VARCHAR(255) NOT NULL,
    hausnummer VARCHAR(20) NOT NULL,
    plz VARCHAR(10) NOT NULL,
    stadt VARCHAR(100) NOT NULL,
    land VARCHAR(100) DEFAULT 'Deutschland',
    ist_aktiv TINYINT(1) NOT NULL DEFAULT 1,
    erstellt_am TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    aktualisiert_am TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (mitarbeiter_id) REFERENCES employees(id) ON DELETE CASCADE,
    INDEX idx_mitarbeiter_id (mitarbeiter_id),
    INDEX idx_ist_aktiv (ist_aktiv)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
