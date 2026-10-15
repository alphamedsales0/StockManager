<?php
// Autor: Alpha Med Care
// upload_image.php - Upload d'image produit
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Keine Datei hochgeladen']);
    exit;
}

$file = $_FILES['image'];

// ============================================================
// Validation du type (MIME + fallback par extension)
// ============================================================
$allowedTypes = [
    'image/jpeg',
    'image/jpg',
    'image/png',
    'image/gif',
    'image/webp',
    'image/avif',
];

$allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'];

// Extension
$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if ($extension === '') $extension = 'jpg';

// MIME détecté par finfo
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

$isValidMime = in_array($mimeType, $allowedTypes, true);
$isValidExt  = in_array($extension, $allowedExt, true);

// ⚠️ Si finfo échoue (application/octet-stream, vide, false),
//    on se rabat sur l'extension du fichier
if (!$isValidMime) {
    if ($isValidExt) {
        // Force le MIME pour la suite
        $mimeType = 'image/' . ($extension === 'jpg' ? 'jpeg' : $extension);
    } else {
        http_response_code(400);
        echo json_encode([
            'success'    => false,
            'error'      => 'Ungültiger Dateityp.',
            'debug_mime' => $mimeType,           // à retirer en prod
            'debug_ext'  => $extension
        ]);
        exit;
    }
}

// ============================================================
// Helper : slugifie une chaîne
// ============================================================
function slugify(string $s): string {
    $s = str_replace(
        ['ä','ö','ü','Ä','Ö','Ü','ß','é','è','ê','à','â','ç','î','ï','ô','û','ù'],
        ['ae','oe','ue','Ae','Oe','Ue','ss','e','e','e','a','a','c','i','i','o','u','u'],
        $s
    );
    $s = strtolower($s);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-');
}

// ============================================================
// 1. Slug du nom original (sans extension)
// ============================================================
$originalBase = pathinfo($file['name'], PATHINFO_FILENAME);
$baseSlug     = slugify($originalBase);
if ($baseSlug === '') $baseSlug = 'produkt';

// ============================================================
// 2. Couleur
// ============================================================
$colorRaw  = isset($_POST['color']) ? trim((string)$_POST['color']) : '';
$colorSlug = $colorRaw !== '' ? slugify($colorRaw) : '';

// ============================================================
// 3. Nom final : {baseSlug}[-{unique}]-{colorSlug}.{ext}
// ============================================================

// Évite le doublon si la couleur est déjà dans le slug
if ($colorSlug !== '' && substr($baseSlug, -strlen($colorSlug)) === $colorSlug) {
    $before = substr($baseSlug, 0, -strlen($colorSlug));
    if ($before === '' || substr($before, -1) === '-') {
        $baseSlug = rtrim($before, '-');
        if ($baseSlug === '') $baseSlug = 'produkt';
    }
}

$uploadDir = __DIR__ . '/../images/products/';
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => "Dossier introuvable : $uploadDir"]);
        exit;
    }
}

$targetFilename = $colorSlug !== ''
    ? $baseSlug . '-' . $colorSlug . '.' . $extension
    : $baseSlug . '.' . $extension;

// Si le fichier existe déjà, suffixe unique
if (file_exists($uploadDir . $targetFilename)) {
    $unique = '-' . str_replace('.', '', substr(uniqid('', true), -6));
    $targetFilename = $colorSlug !== ''
        ? $baseSlug . $unique . '-' . $colorSlug . '.' . $extension
        : $baseSlug . $unique . '.' . $extension;
}

$filename = $targetFilename;

// ============================================================
// 4. Écriture disque
// ============================================================
if (!is_writable($uploadDir)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => "Dossier non inscriptible : $uploadDir"
    ]);
    exit;
}

$uploadPath = $uploadDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'move_uploaded_file a échoué',
        'debug'   => ['dir' => $uploadDir, 'path' => $uploadPath]
    ]);
    exit;
}

// ============================================================
// 5. Chemin public
// ============================================================
$publicUrl = '/stockmanager/images/products/' . $filename;

echo json_encode([
    'success'        => true,
    'url'            => $publicUrl,
    'filename'       => $filename,
    'detected_color' => $colorSlug ?: null,
    'mime_used'      => $mimeType,       // à retirer en prod
    'ext_used'       => $extension
], JSON_UNESCAPED_UNICODE);