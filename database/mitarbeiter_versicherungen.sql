-- Neue Tabelle für Versicherungen
CREATE TABLE IF NOT EXISTS mitarbeiter_versicherungen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mitarbeiter_id INT NOT NULL,
    versicherungstyp VARCHAR(50) NULL,            -- z.B. 'Krankenversicherung'
    versicherungsgesellschaft VARCHAR(100) NULL,  -- Name der Versicherung
    versicherungsnummer VARCHAR(50) NULL,         -- Vertragsnummer
    gueltig_ab DATE NULL,
    gueltig_bis DATE NULL,
    beitrag DECIMAL(10,2) NULL,
    bemerkung TEXT NULL,
    ist_aktiv BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (mitarbeiter_id) REFERENCES employees(id) ON DELETE CASCADE
);
