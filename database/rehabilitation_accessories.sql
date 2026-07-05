CREATE TABLE IF NOT EXISTS rehabilitation_accessories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT NOT NULL,
    material VARCHAR(100) NULL,
    compatibility TEXT NULL,               -- Compatibilité avec d'autres appareils
    usage_area VARCHAR(255) NULL,           -- Einsatzbereich (z.B. Physiotherapie, Ergotherapie)
    weight_kg DECIMAL(5,2) NULL,            -- Gewicht
    dimensions VARCHAR(100) NULL,           -- Abmessungen (LxBxH)
    has_adjustable BOOLEAN DEFAULT FALSE,   -- Verstellbar
    has_certification BOOLEAN DEFAULT FALSE, -- Zertifizierung (z.B. CE, TÜV)
    color_options VARCHAR(255) NULL,
    warranty_years INT NULL,
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
