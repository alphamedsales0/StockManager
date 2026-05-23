<?php
// download_attachment.php
$id = $_GET['id'] ?? 0;
if (!$id) die('Invalid request');

require_once __DIR__ . '/database_connect.php';

$stmt = $pdo->prepare("SELECT file_path, file_name FROM attachments WHERE id = ?");
$stmt->execute([$id]);
$file = $stmt->fetch(PDO::FETCH_ASSOC);

if ($file && file_exists($file['file_path'])) {
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($file['file_name']) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file['file_path']));
    readfile($file['file_path']);
    exit;
} else {
    http_response_code(404);
    echo "File not found";
}