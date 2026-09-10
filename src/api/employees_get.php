<?php
// employees_get.php
// Récupère un employé et toutes ses données associées (par uid OU par id)
// Dernière mise à jour : 2025-09-10
// ✅ Adapté à la structure : employees.uid + tables enfants avec mitarbeiter_uid

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/database_connect.php';

// ---------- Récupération de l'identifiant (uid prioritaire, id en fallback) ----------
$uid = $_GET['uid'] ?? '';
$id  = $_GET['id']  ?? 0;

if (!$uid && !$id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'UID oder ID fehlt']);
    exit;
}

try {
    // ---------- 1. Employé + user ----------
    if ($uid) {
        if (!preg_match('/^[0-9a-f\-]{36}$/i', $uid)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'UID ungültig']);
            exit;
        }
        $stmt = $pdo->prepare("
            SELECT
                u.id AS benutzer_id,
                u.uid AS user_uid,
                u.email,
                u.username,
                u.role,
                u.is_active,
                u.created_at AS user_created_at,
                u.updated_at AS user_updated_at,
                e.*
            FROM users u
            INNER JOIN employees e ON u.id = e.benutzer_id
            WHERE e.uid = ?
            LIMIT 1
        ");
        $stmt->execute([$uid]);
    } else {
        $stmt = $pdo->prepare("
            SELECT
                u.id AS benutzer_id,
                u.uid AS user_uid,
                u.email,
                u.username,
                u.role,
                u.is_active,
                u.created_at AS user_created_at,
                u.updated_at AS user_updated_at,
                e.*
            FROM users u
            INNER JOIN employees e ON u.id = e.benutzer_id
            WHERE u.id = ?
            LIMIT 1
        ");
        $stmt->execute([(int)$id]);
    }

    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$employee) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Mitarbeiter nicht gefunden']);
        exit;
    }

    // Le champ principal pour le front
    $employeeUid = $employee['uid'];

    // ---------- 2. Adresses actives ----------
    $stmtAddr = $pdo->prepare("
        SELECT id, adresstyp, strasse, hausnummer, plz, stadt, land, ist_aktiv
        FROM employees_addresses
        WHERE mitarbeiter_uid = ? AND ist_aktiv = 1
        ORDER BY adresstyp = 'primär' DESC, id ASC
    ");
    $stmtAddr->execute([$employeeUid]);
    $employee['addresses'] = $stmtAddr->fetchAll(PDO::FETCH_ASSOC);

    // ---------- 3. Comptes bancaires ----------
    $stmtBank = $pdo->prepare("
        SELECT id, kontoinhaber, iban, bic, bankname, ist_aktiv, bemerkung, created_at
        FROM employee_bank_accounts
        WHERE mitarbeiter_uid = ?
        ORDER BY ist_aktiv DESC, id ASC
    ");
    $stmtBank->execute([$employeeUid]);
    $employee['bank_accounts'] = $stmtBank->fetchAll(PDO::FETCH_ASSOC);

    // ---------- 4. Qualifications ----------
    $stmtQual = $pdo->prepare("
        SELECT id, qualifikationstyp, bezeichnung, institution,
               abschlussdatum, gueltig_bis, note, datei_pfad
        FROM employee_qualifications
        WHERE mitarbeiter_uid = ?
        ORDER BY id ASC
    ");
    $stmtQual->execute([$employeeUid]);
    $employee['qualifications'] = $stmtQual->fetchAll(PDO::FETCH_ASSOC);

    // ---------- 5. Documents ----------
    $stmtDocs = $pdo->prepare("
        SELECT id, name, typ, datei_pfad, hochgeladen_am, gueltig_bis
        FROM employee_documents
        WHERE mitarbeiter_uid = ?
        ORDER BY id ASC
    ");
    $stmtDocs->execute([$employeeUid]);
    $employee['documents'] = $stmtDocs->fetchAll(PDO::FETCH_ASSOC);

    // ---------- 6. Versicherung (dernière active) ----------
    $stmtVers = $pdo->prepare("
        SELECT id, versicherungstyp, versicherungsgesellschaft, versicherungsnummer,
               gueltig_ab, gueltig_bis, beitrag, ist_aktiv
        FROM mitarbeiter_versicherungen
        WHERE mitarbeiter_uid = ? AND ist_aktiv = 1
        ORDER BY id DESC
        LIMIT 1
    ");
    $stmtVers->execute([$employeeUid]);
    $versicherung = $stmtVers->fetch(PDO::FETCH_ASSOC) ?: null;

    // Aplatir les champs de versicherung dans l'objet principal (comme attendu par le front)
    if ($versicherung) {
        $employee['versicherung_typ']             = $versicherung['versicherungstyp'];
        $employee['versicherung_gesellschaft']    = $versicherung['versicherungsgesellschaft'];
        $employee['versicherung_nummer']          = $versicherung['versicherungsnummer'];
        $employee['versicherung_gueltig_ab']      = $versicherung['gueltig_ab'];
        $employee['versicherung_gueltig_bis']     = $versicherung['gueltig_bis'];
        $employee['versicherung_beitrag']         = $versicherung['beitrag'];
    }

    // ---------- 7. Nettoyage / sécurité ----------
    unset($employee['password_hash']);

    // ---------- 8. Réponse ----------
    echo json_encode([
        'success'  => true,
        'employee' => $employee,
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    file_put_contents(
        __DIR__ . '/debug.log',
        date('Y-m-d H:i:s') . " GET EXCEPTION: " . $e->getMessage() . "\n",
        FILE_APPEND
    );
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}