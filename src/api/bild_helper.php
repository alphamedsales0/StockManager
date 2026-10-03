<?php
// api/bild_helper.php
// Fonction utilitaire pour trouver l'extension réelle d'une image

/**
 * Cache mémoire pour éviter les appels file_exists() répétés
 * (valable pour la durée d'un seul appel HTTP)
 */
$GLOBALS['_bild_extension_cache'] = [];

/**
 * Trouve l'extension réelle d'une image
 *
 * @param string $basePath  Chemin sans extension, ex: "/images/products/cycle-med-4000-weiss"
 * @return string|null      Chemin complet avec extension, ou null si aucun fichier trouvé
 */
function bildMitExtension($basePath) {
    if (empty($basePath)) {
        return null;
    }

    // Déjà avec extension ? → retourner tel quel
    if (preg_match('/\.(jpg|jpeg|png|webp|gif|svg|avif)$/i', $basePath)) {
        return $basePath;
    }

    // Vérifier le cache
    if (isset($GLOBALS['_bild_extension_cache'][$basePath])) {
        return $GLOBALS['_bild_extension_cache'][$basePath];
    }

    // Extensions à tester dans l'ordre (les plus courantes en premier)
    $extensions = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg', 'avif'];

    // Chemin racine du site
    $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';

    // Fallback si DOCUMENT_ROOT est vide (certains hébergeurs)
    if (empty($docRoot)) {
        $docRoot = dirname(__DIR__);
    }

    // Nettoyer le chemin (supprimer les éventuels double slash)
    $cleanBasePath = '/' . ltrim($basePath, '/');
    $docRoot = rtrim($docRoot, '/');

    foreach ($extensions as $ext) {
        $physicalPath = $docRoot . $cleanBasePath . '.' . $ext;
        if (file_exists($physicalPath)) {
            $result = $cleanBasePath . '.' . $ext;
            $GLOBALS['_bild_extension_cache'][$basePath] = $result;
            return $result;
        }
    }

    // Aucune extension trouvée → null (le frontend affichera un placeholder)
    $GLOBALS['_bild_extension_cache'][$basePath] = null;
    return null;
}

/**
 * Version courte : renvoie directement l'URL avec extension
 * ou une URL par défaut si l'image n'existe pas
 */
function bildUrlMitFallback($basePath, $fallback = null) {
    $result = bildMitExtension($basePath);
    return $result ?: $fallback;
}