<?php
// api/database.php
$host = 'db5018574434.hosting-data.io';
$dbname = 'dbs14737411';
$user = 'dbu2173288';
$pass = 'cc4ef!NQfm4UAFs';

try {
    $dbh = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $dbh->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Datenbankverbindung fehlgeschlagen: ' . $e->getMessage()]);
    exit;
}