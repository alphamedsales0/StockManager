-- ============================================================
-- TABLE artikeltypen
-- ============================================================
USE `dbs14737411`;

DROP TABLE IF EXISTS `artikeltypen`;

CREATE TABLE `artikeltypen` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `schluessel` VARCHAR(50) NOT NULL,
  `bezeichnung` VARCHAR(150) NOT NULL,
  `beschreibung` VARCHAR(255) NULL,
  `sortierung` INT NOT NULL DEFAULT 0,
  `ist_aktiv` TINYINT(1) NOT NULL DEFAULT 1,
  `erstellt_am` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `aktualisiert_am` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_artikeltypen_schluessel` (`schluessel`),
  KEY `idx_artikeltypen_aktiv` (`ist_aktiv`, `sortierung`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DONNÉES — basées sur les artikeltyp utilisés dans ton SQL
-- ============================================================
INSERT INTO `artikeltypen` (`schluessel`, `bezeichnung`, `sortierung`) VALUES
  ('fahrrad',          'Fahrrad (Ergometer)',    1),
  ('liegeergometer',   'Liegeergometer',         2),
  ('bewegungskreis',   'Bewegungskreis',         3),
  ('crosstrainer',     'Crosstrainer',           4),
  ('laufband',         'Laufband',               5),
  ('kraft',            'Kraftgerät',             6),
  ('reha_zubehoer',    'Reha-Zubehör',           7);

-- ============================================================
-- VÉRIFICATION
-- ============================================================
SELECT * FROM artikeltypen ORDER BY sortierung;
