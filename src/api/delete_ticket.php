<?php
// delete_ticket.php
require_once __DIR__ . '/permission_helper.php';
applyCors();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

require_once __DIR__ . '/database_connect.php';
require_once __DIR__ . '/permission_helper.php';

// ✅ Contrôle d'accès : Admin UNIQUEMENT
requireRoles(['Admin']);

if (!isset($pdo) || !$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'DB connection failed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$ticketId = (int)($input['ticket_id'] ?? 0);
$source   = trim($input['source'] ?? 'form');

if (!$ticketId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ticket_id required']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Ermitteln der Tabelle anhand der Quelle
    $table = ($source === 'angebot') ? 'angebot_requests' : 'form_submissions';

    // 2. Zuerst alle verknüpften Daten löschen (wenn Kaskaden nicht existieren)
    $tablesToClean = [
        'comments'        => 'ticket_id',
        'internal_notes'  => 'ticket_id',
        'time_entries'    => 'ticket_id',
        'attachments'     => 'ticket_id'
    ];

    foreach ($tablesToClean as $tableName => $col) {
        try {
            $del = $pdo->prepare("DELETE FROM $tableName WHERE $col = ?");
            $del->execute([$ticketId]);
        } catch (PDOException $e) {
            // Ignorieren, wenn Tabelle nicht existiert
            error_log("delete_ticket: skip $tableName — " . $e->getMessage());
        }
    }

    // 3. Anhänge auf dem Dateisystem löschen (optional)
    try {
        $stmtFiles = $pdo->prepare("SELECT file_path FROM attachments WHERE ticket_id = ?");
        $stmtFiles->execute([$ticketId]);
        $files = $stmtFiles->fetchAll(PDO::FETCH_COLUMN);
        foreach ($files as $path) {
            if ($path && file_exists($path)) {
                @unlink($path);
            }
        }
    } catch (PDOException $e) {
        error_log("delete_ticket: files cleanup — " . $e->getMessage());
    }

    // 4. Ticket selbst löschen
    $stmt = $pdo->prepare("DELETE FROM $table WHERE id = ?");
    $stmt->execute([$ticketId]);

    if ($stmt->rowCount() === 0) {
        throw new Exception('Ticket nicht gefunden oder bereits gelöscht.');
    }

    $pdo->commit();

    echo json_encode([
        'success'    => true,
        'message'    => 'Ticket erfolgreich gelöscht.',
        'deleted_id' => $ticketId,
        'deleted_by' => currentUserName()
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}