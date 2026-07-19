<?php
// api/employees.php
// Gestion CORS dynamique (identique à stock_manager_products.php)
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');
}

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    }
    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
    }
    exit(0);
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/database_connect.php';

// ------------------------------------------------------------
// 1. GET : liste ou détail
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    try {
        if ($id > 0) {
            // Détail d'un employé
            $stmt = $pdo->prepare("
                SELECT
                    u.id AS benutzer_id,
                    u.email,
                    u.is_active,
                    e.*
                FROM users u
                JOIN employees e ON u.id = e.benutzer_id
                WHERE u.id = ? AND u.role = 'employee'
            ");
            $stmt->execute([$id]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$employee) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Mitarbeiter nicht gefunden']);
                exit;
            }

            // Adresses actives
            $stmtAddr = $pdo->prepare("
                SELECT id, adresstyp, strasse, hausnummer, plz, stadt, land, ist_aktiv
                FROM employees_addresses
                WHERE mitarbeiter_id = ? AND ist_aktiv = 1
            ");
            $stmtAddr->execute([$employee['id']]);
            $addresses = $stmtAddr->fetchAll(PDO::FETCH_ASSOC);
            $employee['addresses'] = $addresses;

            unset($employee['password_hash']); // sécurité
            echo json_encode(['success' => true, 'employee' => $employee]);
        } else {
            // Liste de tous les employés
            $stmt = $pdo->query("
                SELECT
                    u.id AS benutzer_id,
                    u.email,
                    u.is_active,
                    e.id AS mitarbeiter_id,
                    e.mitarbeiter_nummer,
                    e.vorname,
                    e.nachname,
                    e.telefon,
                    e.mobil,
                    e.position,
                    e.abteilung,
                    e.einstellungsdatum,
                    ea.strasse,
                    ea.hausnummer,
                    ea.plz,
                    ea.stadt,
                    ea.land
                FROM users u
                JOIN employees e ON u.id = e.benutzer_id
                LEFT JOIN employees_addresses ea ON e.id = ea.mitarbeiter_id AND ea.adresstyp = 'primär' AND ea.ist_aktiv = 1
                WHERE u.role = 'employee'
                ORDER BY e.nachname, e.vorname
            ");
            $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'employees' => $employees]);
        }
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ------------------------------------------------------------
// 2. POST : créer un employé
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Données JSON invalides']);
        exit;
    }

    // Log
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

        // 3. Adresse primaire si fournie
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
    exit;
}

// ------------------------------------------------------------
// 3. PUT : mettre à jour un employé
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID fehlt (paramètre ?id=...)']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Données JSON invalides']);
        exit;
    }

    // Vérifier que l'utilisateur existe
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'employee'");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Mitarbeiter nicht gefunden']);
        exit;
    }

    $pdo->beginTransaction();
    try {
        // Mise à jour users (email + password éventuel)
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
    exit;
}

// ------------------------------------------------------------
// 4. DELETE : supprimer un employé
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID fehlt (paramètre ?id=...)']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'employee'");
        $stmt->execute([$id]);
        if ($stmt->rowCount() == 0) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Mitarbeiter nicht gefunden']);
            exit;
        }
        echo json_encode(['success' => true, 'message' => 'Mitarbeiter gelöscht']);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Si la méthode HTTP n'est pas prise en charge
http_response_code(405);
echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);