<?php
// api/marken_helper.php
// Helpers pour garantir la cohérence entre artikel.marke et marken.name

/**
 * Génère un schluessel à partir d'un nom de marque.
 *  - minuscules
 *  - accents / caractères spéciaux → tirets
 *  - tirets consécutifs fusionnés
 */
function markeMakeSchluessel(string $name): string {
    // Translittération basique des accents (ä → ae, ö → oe, ü → ue, ß → ss)
    $name = str_replace(
        ['ä','ö','ü','Ä','Ö','Ü','ß'],
        ['ae','oe','ue','Ae','Oe','Ue','ss'],
        $name
    );
    $slug = strtolower(trim($name));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug !== '' ? $slug : 'marke-' . time();
}

/**
 * Normalise une valeur qui peut venir du front (string, object {id,name}, null).
 */
function markeNormalize($value): string {
    if (is_array($value)) {
        return isset($value['name']) ? trim((string)$value['name']) : '';
    }
    return trim((string)$value);
}

/**
 * Vérifie que la marque existe dans `marken`.
 *  - Si oui : retourne le nom canonique (marken.name)
 *  - Si non : crée la marque (avec un schluessel unique) et retourne le nom tel quel
 *  - Si vide : retourne null
 *
 * @throws Exception si la marque est invalide
 */
function markeEnsureExists(PDO $dbh, ?string $name): ?string {
    $name = markeNormalize($name);

    if ($name === '') {
        return null;
    }

    if (mb_strlen($name) > 150) {
        throw new Exception("Markenname zu lang (max 150 Zeichen): " . mb_substr($name, 0, 50) . '...');
    }

    // 1. Recherche insensible à la casse
    $stmt = $dbh->prepare("SELECT id, name FROM marken WHERE LOWER(name) = LOWER(?) LIMIT 1");
    $stmt->execute([$name]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        return $existing['name']; // nom canonique
    }

    // 2. Création automatique
    $schluessel = markeMakeSchluessel($name);

    // Unicité du schluessel
    $check = $dbh->prepare("SELECT id FROM marken WHERE schluessel = ? LIMIT 1");
    $check->execute([$schluessel]);
    if ($check->fetch()) {
        $schluessel .= '-' . time();
    }

    $insert = $dbh->prepare("
        INSERT INTO marken (schluessel, name, ist_aktiv)
        VALUES (?, ?, 1)
    ");
    $insert->execute([$schluessel, $name]);

    return $name;
}