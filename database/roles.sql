-- Tabelle für Rollen
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    display_name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Standard-Rollen einfügen (inkl. Techniker)
INSERT INTO roles (name, display_name, description) VALUES
('admin', 'Administrator', 'Vollzugriff auf alle Bereiche'),
('manager', 'Manager', 'Führungskraft mit erweiterten Rechten'),
('hr', 'Personal', 'Personalabteilung'),
('finance', 'Finanzen', 'Finanz- & Buchhaltung'),
('sales', 'Vertrieb', 'Vertriebsmitarbeiter'),
('warehouse', 'Lager', 'Lager & Logistik'),
('purchasing', 'Einkauf', 'Einkaufsabteilung'),
('support', 'Support', 'Kundensupport'),
('teamlead', 'Teamleiter', 'Teamleitung'),
('employee', 'Mitarbeiter', 'Standard-Mitarbeiter'),
('accountant', 'Buchhalter', 'Buchhaltung'),
('developer', 'Entwickler', 'Entwicklung'),
('technician', 'Techniker', 'Technischer Service & Support')
ON DUPLICATE KEY UPDATE display_name = VALUES(display_name), description = VALUES(description);
