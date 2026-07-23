-- Tabelle für Kategorien
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,           -- Anzeigename (z. B. "Kardio")
    value VARCHAR(255) UNIQUE NOT NULL,   -- technischer Wert (z. B. "cardio")
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabelle für Artikeltypen
CREATE TABLE article_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,           -- Anzeigename (z. B. "Laufband")
    value VARCHAR(255) UNIQUE NOT NULL,   -- technischer Wert (z. B. "treadmill")
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


INSERT INTO categories (name, value) VALUES
('Kardio', 'cardio'),
('Kraft', 'strength'),
('Rehabilitation', 'rehabilitation'),
('Zubehör', 'accessories');

INSERT INTO article_types (name, value) VALUES
('Laufband', 'treadmill'),
('Fahrrad', 'bike'),
('Kraftgerät', 'strength'),
('Reha-Zubehör', 'rehabilitation_accessory');
