-- Qualifikationen
CREATE TABLE employee_qualifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mitarbeiter_id INT NOT NULL,
    qualifikationstyp ENUM('Schulabschluss','Studium','Ausbildung','Zertifikat','Sprache','Führerschein'),
    bezeichnung VARCHAR(150) NOT NULL,
    institution VARCHAR(100),
    abschlussdatum DATE,
    gueltig_bis DATE,
    note VARCHAR(10),
    datei_pfad VARCHAR(255),
    FOREIGN KEY (mitarbeiter_id) REFERENCES employees(id) ON DELETE CASCADE
);
