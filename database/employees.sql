-- ============================================================
-- 2. EMPLOYEES
-- ============================================================
CREATE TABLE employees (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    uid CHAR(36) NOT NULL UNIQUE COMMENT 'UUID v4 public',
    benutzer_id INT(11) NOT NULL UNIQUE,
    mitarbeiter_nummer VARCHAR(20) UNIQUE NULL,
    vorname VARCHAR(50) NOT NULL,
    nachname VARCHAR(50) NOT NULL,
    telefon VARCHAR(20) NULL,
    mobil VARCHAR(20) NULL,
    position VARCHAR(100) NULL,
    abteilung VARCHAR(100) NULL,
    einstellungsdatum DATE NULL,
    geburtsdatum DATE NULL,
    gehalt DECIMAL(12,2) NULL,
    notfall_kontakt_name VARCHAR(100) NULL,
    notfall_kontakt_telefon VARCHAR(20) NULL,
    erstellt_am TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    aktualisiert_am TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    steuer_id VARCHAR(30) NULL,
    sozialversicherungsnummer VARCHAR(20) NULL,
    vertragsart VARCHAR(50) NULL,
    wochenarbeitszeit DECIMAL(4,2) NULL,
    steuerklasse VARCHAR(2) NULL DEFAULT '1',
    konfession VARCHAR(20) NULL DEFAULT 'keine',
    vorgesetzter VARCHAR(255) NULL DEFAULT NULL,
    foto_pfad VARCHAR(255) NULL DEFAULT NULL,
    FOREIGN KEY (benutzer_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_uid (uid),
    INDEX idx_nachname (nachname),
    INDEX idx_abteilung (abteilung),
    INDEX idx_position (position),
    INDEX idx_einstellungsdatum (einstellungsdatum),
    INDEX idx_mitarbeiter_nummer (mitarbeiter_nummer)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 3. EMPLOYEES_ADDRESSES
-- ============================================================
CREATE TABLE employees_addresses (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    mitarbeiter_uid CHAR(36) NOT NULL,
    adresstyp VARCHAR(20) DEFAULT 'primär',
    strasse VARCHAR(255) NOT NULL,
    hausnummer VARCHAR(20) NOT NULL,
    plz VARCHAR(10) NOT NULL,
    stadt VARCHAR(100) NOT NULL,
    land VARCHAR(100) DEFAULT 'Deutschland',
    ist_aktiv TINYINT(1) NOT NULL DEFAULT 1,
    erstellt_am TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    aktualisiert_am TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (mitarbeiter_uid) REFERENCES employees(uid) ON DELETE CASCADE,
    INDEX idx_mitarbeiter_uid (mitarbeiter_uid),
    INDEX idx_ist_aktiv (ist_aktiv)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 4. EMPLOYEE_BANK_ACCOUNTS
-- ============================================================
CREATE TABLE employee_bank_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mitarbeiter_uid CHAR(36) NOT NULL,
    kontoinhaber VARCHAR(100) NOT NULL,
    iban VARCHAR(34) NOT NULL,
    bic VARCHAR(11),
    bankname VARCHAR(100),
    ist_aktiv BOOLEAN DEFAULT 0,
    bemerkung TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mitarbeiter_uid) REFERENCES employees(uid) ON DELETE CASCADE,
    INDEX idx_mitarbeiter_uid (mitarbeiter_uid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 5. EMPLOYEE_DOCUMENTS
-- ============================================================
CREATE TABLE employee_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mitarbeiter_uid CHAR(36) NOT NULL,
    name VARCHAR(100) NOT NULL,
    typ ENUM('Vertrag','Zeugnis','Zertifikat','Pass','Arbeitserlaubnis','Sonstiges'),
    datei_pfad VARCHAR(255) NOT NULL,
    hochgeladen_am DATETIME DEFAULT CURRENT_TIMESTAMP,
    gueltig_bis DATE,
    FOREIGN KEY (mitarbeiter_uid) REFERENCES employees(uid) ON DELETE CASCADE,
    INDEX idx_mitarbeiter_uid (mitarbeiter_uid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 6. EMPLOYEE_QUALIFICATIONS
-- ============================================================
CREATE TABLE employee_qualifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mitarbeiter_uid CHAR(36) NOT NULL,
    qualifikationstyp ENUM('Schulabschluss','Studium','Ausbildung','Zertifikat','Sprache','Führerschein'),
    bezeichnung VARCHAR(150) NOT NULL,
    institution VARCHAR(100),
    abschlussdatum DATE,
    gueltig_bis DATE,
    note VARCHAR(10),
    datei_pfad VARCHAR(255),
    FOREIGN KEY (mitarbeiter_uid) REFERENCES employees(uid) ON DELETE CASCADE,
    INDEX idx_mitarbeiter_uid (mitarbeiter_uid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 7. MITARBEITER_VERSICHERUNGEN
-- ============================================================
CREATE TABLE mitarbeiter_versicherungen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mitarbeiter_uid CHAR(36) NOT NULL,
    versicherungstyp VARCHAR(50) NULL,
    versicherungsgesellschaft VARCHAR(100) NULL,
    versicherungsnummer VARCHAR(50) NULL,
    gueltig_ab DATE NULL,
    gueltig_bis DATE NULL,
    beitrag DECIMAL(10,2) NULL,
    bemerkung TEXT NULL,
    ist_aktiv BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (mitarbeiter_uid) REFERENCES employees(uid) ON DELETE CASCADE,
    INDEX idx_mitarbeiter_uid (mitarbeiter_uid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
