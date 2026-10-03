USE `dbs14737411`;

CREATE TABLE IF NOT EXISTS `artikel_verlauf` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `artikel_id` INT UNSIGNED NOT NULL,
  `benutzer` VARCHAR(100) NULL COMMENT 'Utilisateur qui a fait la modif',
  `aktion` VARCHAR(50) NOT NULL COMMENT 'create, update, delete, duplicate',
  `feld` VARCHAR(100) NULL COMMENT 'Champ modifié (si update)',
  `alter_wert` TEXT NULL,
  `neuer_wert` TEXT NULL,
  `beschreibung` VARCHAR(255) NULL,
  `erstellt_am` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_verlauf_artikel` (`artikel_id`),
  KEY `idx_verlauf_datum` (`erstellt_am`),
  CONSTRAINT `fk_verlauf_artikel`
    FOREIGN KEY (`artikel_id`) REFERENCES `artikel` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

USE `dbs14737411`;

-- 1. Supprimer la contrainte CASCADE
ALTER TABLE `artikel_verlauf` DROP FOREIGN KEY `fk_verlauf_artikel`;

-- 2. (Optionnel mais recommandé) Rendre artikel_id nullable
--    pour garder une trace même après suppression du produit
ALTER TABLE `artikel_verlauf`
  MODIFY `artikel_id` INT UNSIGNED NULL;

-- 3. Ajouter un index pour retrouver l'historique après suppression
ALTER TABLE `artikel_verlauf` ADD INDEX `idx_verlauf_artikel_nummer` (`artikel_id`);

ALTER TABLE `artikel_verlauf`
  ADD COLUMN `artikel_nummer` VARCHAR(100) NULL
    COMMENT 'Numéro d\'article figé (utile si produit supprimé)'
    AFTER `artikel_id`;
