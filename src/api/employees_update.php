<?php
// employees_update.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: PUT, OPTIONS");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/database_connect.php';

$id = $_GET['id'] ?? 0;
if (!$id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID fehlt']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

// Vérifier que l'utilisateur existe et est un employé
$stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'employee'");
$stmt->execute([$id]);
if (!$stmt->fetch()) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Mitarbeiter nicht gefunden']);
    exit;
}

$pdo->beginTransaction();
try {
    // Mise à jour users (email et mot de passe)
    $email = $input['email'] ?? null;
    $password = $input['password'] ?? null;
    $updates = [];
    $params = [];
    if ($email) {
        $updates[] = "email = ?";
        $params[] = $email;
    }
    if ($password) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $updates[] = "password_hash = ?";
        $params[] = $hashed;
    }
    if (!empty($updates)) {
        $updates[] = "updated_at = NOW()";
        $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
        $params[] = $id;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }

    // Mise à jour employees
    $stmt = $pdo->prepare("
        UPDATE employees SET
            mitarbeiter_nummer = ?,
            vorname = ?,
            nachname = ?,
            telefon = ?,
            mobil = ?,
            position = ?,
            abteilung = ?,
            einstellungsdatum = ?,
            geburtsdatum = ?,
            gehalt = ?,
            notfall_kontakt_name = ?,
            notfall_kontakt_telefon = ?,
            aktualisiert_am = NOW()
        WHERE benutzer_id = ?
    ");
    $stmt->execute([
        $input['mitarbeiter_nummer'] ?? null,
        $input['vorname'] ?? null,
        $input['nachname'] ?? null,
        $input['telefon'] ?? null,
        $input['mobil'] ?? null,
        $input['position'] ?? null,
        $input['abteilung'] ?? null,
        $input['einstellungsdatum'] ?? null,
        $input['geburtsdatum'] ?? null,
        $input['gehalt'] ?? null,
        $input['notfall_kontakt_name'] ?? null,
        $input['notfall_kontakt_telefon'] ?? null,
        $id
    ]);

    // Gestion des adresses
    $stmt = $pdo->prepare("SELECT id FROM employees WHERE benutzer_id = ?");
    $stmt->execute([$id]);
    $emp = $stmt->fetch();
    if ($emp) {
        $mitarbeiter_id = $emp['id'];
        // Supprimer les anciennes adresses actives
        $stmt = $pdo->prepare("DELETE FROM employees_addresses WHERE mitarbeiter_id = ? AND ist_aktiv = 1");
        $stmt->execute([$mitarbeiter_id]);

        // Insérer les nouvelles adresses
        if (isset($input['addresses']) && is_array($input['addresses'])) {
            foreach ($input['addresses'] as $addr) {
                if (empty($addr['strasse']) || empty($addr['hausnummer'])) continue;
                $stmt = $pdo->prepare("
                    INSERT INTO employees_addresses (
                        mitarbeiter_id, adresstyp, strasse, hausnummer, plz, stadt, land, ist_aktiv
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $mitarbeiter_id,
                    $addr['adresstyp'] ?? 'primär',
                    $addr['strasse'],
                    $addr['hausnummer'],
                    $addr['plz'] ?? null,
                    $addr['stadt'] ?? null,
                    $addr['land'] ?? 'Deutschland',
                    $addr['ist_aktiv'] ?? 1
                ]);
            }
        } elseif (isset($input['adresse']) && !empty($input['adresse']['strasse'])) {
            $addr = $input['adresse'];
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
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Mitarbeiter aktualisiert']);

} catch (PDOException $e) {
    $pdo->rollBack();
    if ($e->errorInfo[1] == 1062) {
        http_response_code(409);
        echo json_encode(['success' => false, 'error' => 'E-Mail oder Mitarbeiter-Nummer existiert bereits']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} catch (Throwable $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}