-- Dokumente
CREATE TABLE employee_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mitarbeiter_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    typ ENUM('Vertrag','Zeugnis','Zertifikat','Pass','Arbeitserlaubnis','Sonstiges'),
    datei_pfad VARCHAR(255) NOT NULL,
    hochgeladen_am DATETIME DEFAULT CURRENT_TIMESTAMP,
    gueltig_bis DATE,
    FOREIGN KEY (mitarbeiter_id) REFERENCES employees(id) ON DELETE CASCADE
);
