<?php
// upload_attachment.php
ob_clean(); // Vorherigen Puffer löschen
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

require_once __DIR__ . '/database_connect.php';

$ticketId = $_POST['ticket_id'] ?? null;
if (!$ticketId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ticket_id missing']);
    exit;
}

$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

$uploadedFiles = [];
foreach ($_FILES['files']['tmp_name'] as $index => $tmpName) {
    $originalName = $_FILES['files']['name'][$index];
    $size = $_FILES['files']['size'][$index];
    $mime = $_FILES['files']['type'][$index];
    
    $safeName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
    $targetPath = $uploadDir . $safeName;
    
    if (move_uploaded_file($tmpName, $targetPath)) {
        $stmt = $pdo->prepare("INSERT INTO attachments (ticket_id, file_name, file_path, file_size, mime_type, uploaded_by) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$ticketId, $originalName, $targetPath, $size, $mime, $_POST['uploaded_by'] ?? 'Admin']);
        $uploadedFiles[] = ['name' => $originalName, 'path' => $targetPath];
    }
}

echo json_encode(['success' => true, 'files' => $uploadedFiles]);