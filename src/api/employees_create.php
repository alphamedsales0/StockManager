<?php
// employees_create.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header('Content-Type: application/json; charset=utf-8');

// Gestion OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/database_connect.php';

$input = json_decode(file_get_contents('php://input'), true);

// Log des données reçues
file_put_contents(__DIR__ . '/debug.log', date('Y-m-d H:i:s') . " INPUT: " . json_encode($input) . "\n", FILE_APPEND);

$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';
$vorname = trim($input['vorname'] ?? '');
$nachname = trim($input['nachname'] ?? '');

if (!$email || !$password || !$vorname || !$nachname) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'E-Mail, Passwort, Vorname und Nachname sind erforderlich']);
    exit;
}

$pdo->beginTransaction();

try {
    // 1. Insérer dans users
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $name = $vorname . ' ' . $nachname;
    $stmt = $pdo->prepare("
        INSERT INTO users (name, email, password_hash, role, is_active, created_at, updated_at)
        VALUES (?, ?, ?, 'employee', 1, NOW(), NOW())
    ");
    $stmt->execute([$name, $email, $hashed]);
    $benutzer_id = $pdo->lastInsertId();
    file_put_contents(__DIR__ . '/debug.log', "User inserted with ID $benutzer_id\n", FILE_APPEND);

    // 2. Insérer dans employees
    $stmt = $pdo->prepare("
        INSERT INTO employees (
            benutzer_id, mitarbeiter_nummer, vorname, nachname, telefon, mobil,
            position, abteilung, einstellungsdatum, geburtsdatum, gehalt,
            notfall_kontakt_name, notfall_kontakt_telefon, aktualisiert_am
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $mitarbeiter_nummer = empty($input['mitarbeiter_nummer']) ? null : $input['mitarbeiter_nummer'];
    $stmt->execute([
        $benutzer_id,
        $mitarbeiter_nummer,
        $vorname,
        $nachname,
        empty($input['telefon']) ? null : $input['telefon'],
        empty($input['mobil']) ? null : $input['mobil'],
        empty($input['position']) ? null : $input['position'],
        empty($input['abteilung']) ? null : $input['abteilung'],
        empty($input['einstellungsdatum']) ? null : $input['einstellungsdatum'],
        empty($input['geburtsdatum']) ? null : $input['geburtsdatum'],
        empty($input['gehalt']) ? null : $input['gehalt'],
        empty($input['notfall_kontakt_name']) ? null : $input['notfall_kontakt_name'],
        empty($input['notfall_kontakt_telefon']) ? null : $input['notfall_kontakt_telefon']
    ]);
    $mitarbeiter_id = $pdo->lastInsertId();
    file_put_contents(__DIR__ . '/debug.log', "Employee inserted with ID $mitarbeiter_id\n", FILE_APPEND);

    // 3. Insérer l'adresse primaire si fournie
    $addr = $input['adresse'] ?? null;
    if ($addr && !empty($addr['strasse']) && !empty($addr['hausnummer'])) {
        $stmt = $pdo->prepare("
            INSERT INTO employees_addresses (
                mitarbeiter_id, adresstyp, strasse, hausnummer, plz, stadt, land, ist_aktiv
            ) VALUES (?, 'primär', ?, ?, ?, ?, ?, 1)
        ");
        $stmt->execute([
            $mitarbeiter_id,
            $addr['strasse'],
            $addr['hausnummer'],
            $addr['plz'] ?? null,
            $addr['stadt'] ?? null,
            $addr['land'] ?? 'Deutschland'
        ]);
        file_put_contents(__DIR__ . '/debug.log', "Address inserted\n", FILE_APPEND);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Mitarbeiter angelegt', 'id' => $benutzer_id]);

} catch (PDOException $e) {
    $pdo->rollBack();
    file_put_contents(__DIR__ . '/debug.log', "PDO ERROR: " . $e->getMessage() . " - Code: " . $e->getCode() . "\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} catch (Throwable $e) {
    $pdo->rollBack();
    file_put_contents(__DIR__ . '/debug.log', "GENERAL ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}