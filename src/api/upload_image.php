<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Vérifier si un fichier a été envoyé
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No file uploaded or upload error']);
    exit;
}

$file = $_FILES['image'];

// Valider le type de fichier
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedTypes)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid file type. Only JPG, PNG, GIF, WEBP allowed.']);
    exit;
}

// Générer un nom unique
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = uniqid() . '.' . $extension;
$uploadDir = __DIR__ . '/../images/shop/';

// Créer le dossier s'il n'existe pas
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$uploadPath = $uploadDir . $filename;

// Déplacer le fichier
if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to save file']);
    exit;
}

// Retourner l'URL publique
$publicUrl = 'https://alpha-med-care.com/images/shop/' . $filename;
echo json_encode(['success' => true, 'url' => $publicUrl]);
?>