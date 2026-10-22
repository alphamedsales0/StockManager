<?php
// api/auth.php

// ============================================================
// ERREURS (ne pas casser le JSON)
// ============================================================
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// ============================================================
// CORS
// ============================================================
$allowed_origins = [
    'https://alpha-med-care.com',
    'https://www.alpha-med-care.com',
    'http://localhost:5173',
    'http://localhost:3000',
    'http://127.0.0.1:5173',
    'http://127.0.0.1:3000'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if ($origin !== '') {
    if (in_array($origin, $allowed_origins, true)) {
        header("Access-Control-Allow-Origin: $origin");
        header('Access-Control-Allow-Credentials: true');
        header('Vary: Origin');
    } else {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Origin nicht erlaubt: ' . $origin]);
        exit;
    }
}

header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// ============================================================
// SESSION
// ============================================================
$isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
         || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
         || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

session_name('ALPHAMEDCARE');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => $isSecure,
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

require_once 'database_connect.php';
require_once 'send_welcome_email.php';

// ============================================================
// HELPERS
// ============================================================

function generateKundennummer($pdo) {
    do {
        $kundennummer = 'K' . date('Ymd') . '-' . str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        $stmt = $pdo->prepare("SELECT id FROM kunden WHERE kundennummer = ?");
        $stmt->execute([$kundennummer]);
    } while ($stmt->fetch());
    return $kundennummer;
}

/**
 * Construit l'objet user renvoyé au frontend.
 * Gère à la fois Privatkunde (vorname/nachname) et Gewerbekunde (firma/kontakt).
 */
function buildUserPayload($user, $phone = '') {
    return [
        'id'         => (int) $user['id'],
        'firstName'  => $user['vorname']  ?? '',
        'lastName'   => $user['nachname'] ?? '',
        'email'      => $user['email'] ?? '',
        'personType' => $user['person_type'] ?? '',
        'phone'      => $phone ?: '',
        'firma'      => $user['firma']    ?? '',
        'kontakt'    => $user['kontakt']  ?? ''
    ];
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = [];
}

$response = ['success' => false, 'message' => ''];

try {

    // ============================================================
    // DEBUG SESSION
    // ============================================================
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'debug_session') {
        $response['success'] = true;
        $response['session_id'] = session_id();
        $response['session_name'] = session_name();
        $response['session_data'] = $_SESSION;
        $response['cookies_received'] = $_COOKIE;
        $response['is_secure'] = $isSecure;
        $response['origin'] = $origin;
        $response['cookie_params'] = session_get_cookie_params();
        $response['save_path'] = session_save_path();
        $response['save_path_writable'] = is_writable(session_save_path());
    }

    // ============================================================
    // REGISTRATION
    // ============================================================
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['action'] ?? '') === 'register') {

        $personType  = $input['personType'] ?? '';
        $anrede      = $input['anrede'] ?? '';
        $firstName   = $input['firstName'] ?? '';
        $lastName    = $input['lastName'] ?? '';
        $email       = $input['email'] ?? '';
        $phone       = $input['phone'] ?? '';
        $phoneType   = $input['phoneType'] ?? '';
        $password    = $input['password'] ?? '';

        $firma       = $input['firma'] ?? '';
        $zusatz      = $input['zusatz'] ?? '';
        $kontakt     = $input['kontakt'] ?? '';
        $hrbRegister = $input['hrbRegister'] ?? '';
        $hrbGericht  = $input['hrbGericht'] ?? '';
        $address     = $input['address'] ?? null;

        $agbAccepted         = isset($input['agbAccepted']) && $input['agbAccepted'] ? 1 : 0;
        $datenschutzAccepted = isset($input['datenschutzAccepted']) && $input['datenschutzAccepted'] ? 1 : 0;

        // --- Validations ---
        if (empty($personType) || !in_array($personType, ['Privatkunde', 'Gewerbekunde'])) {
            throw new Exception('Bitte wählen Sie einen Kundentyp.');
        }
        if (empty($firstName) || empty($lastName) || empty($email) || empty($phone) || empty($password)) {
            throw new Exception('Alle Pflichtfelder müssen ausgefüllt sein.');
        }
        if (empty($anrede)) {
            throw new Exception('Bitte wählen Sie eine Anrede.');
        }
        if ($personType === 'Gewerbekunde' && empty($firma)) {
            throw new Exception('Bei Gewerbekunden muss die Firma angegeben werden.');
        }
        if (strlen($password) < 8) {
            throw new Exception('Das Passwort muss mindestens 8 Zeichen lang sein.');
        }
        if (!$address || empty($address['strasse']) || empty($address['hausnummer']) || empty($address['plz']) || empty($address['ort'])) {
            throw new Exception('Bitte geben Sie eine vollständige Adresse an.');
        }
        if (empty($phoneType) || !in_array($phoneType, ['Telefon', 'Mobil', 'Fax'])) {
            throw new Exception('Bitte wählen Sie einen gültigen Telefontyp.');
        }

        $addressTyp = $address['typ'] ?? '';
        if (empty($addressTyp) || !in_array($addressTyp, ['Rechnungsadresse', 'Lieferadresse', 'Privatadresse', 'Geschäftsadresse'])) {
            throw new Exception('Bitte wählen Sie einen gültigen Adresstyp.');
        }

        // Vérif email unique
        $stmt = $pdo->prepare("SELECT id FROM kunden_authentifizierung WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            throw new Exception('Diese E-Mail-Adresse ist bereits registriert.');
        }

        $pdo->beginTransaction();

        $kundennummer = generateKundennummer($pdo);
        $stmt = $pdo->prepare("INSERT INTO kunden (kundennummer, person_type) VALUES (?, ?)");
        $stmt->execute([$kundennummer, $personType]);
        $kundeId = $pdo->lastInsertId();

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO kunden_authentifizierung (kunde_id, email, passwort_hash, agb_accepted, datenschutz_accepted) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$kundeId, $email, $passwordHash, $agbAccepted, $datenschutzAccepted]);

        $stmt = $pdo->prepare("INSERT INTO kunden_kommunikation (kunde_id, typ, wert, ist_standard) VALUES (?, ?, ?, 1)");
        $stmt->execute([$kundeId, $phoneType, $phone]);

        if ($personType === 'Privatkunde') {
            $stmt = $pdo->prepare("INSERT INTO kunden_personendaten (kunde_id, anrede, vorname, nachname) VALUES (?, ?, ?, ?)");
            $stmt->execute([$kundeId, $anrede, $firstName, $lastName]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO kunden_gewerbedaten (kunde_id, firma, zusatz, kontakt, hrb_register_nummer, hrb_gerichtsstand) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$kundeId, $firma, $zusatz, $kontakt, $hrbRegister, $hrbGericht]);
        }

        $stmt = $pdo->prepare("
            INSERT INTO kunden_adressen 
            (kunde_id, typ, strasse, hausnummer, adresszusatz, plz, ort, bundesland, land, postfach, ist_standard) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $kundeId,
            $addressTyp,
            $address['strasse'] ?? '',
            $address['hausnummer'] ?? '',
            $address['adresszusatz'] ?? '',
            $address['plz'] ?? '',
            $address['ort'] ?? '',
            $address['bundesland'] ?? '',
            $address['land'] ?? 'Deutschland',
            $address['postfach'] ?? '',
            $address['ist_standard'] ?? 1
        ]);

        $pdo->commit();

        // --- Session ---
        $_SESSION['user_id']         = $kundeId;
        $_SESSION['kunde_id']        = $kundeId;
        $_SESSION['user_email']      = $email;
        $_SESSION['user_first_name'] = $firstName;
        $_SESSION['user_last_name']  = $lastName;
        $_SESSION['person_type']     = $personType;

        // ============================================================
        //  EMAIL DE BIENVENUE
        // ============================================================
        try {
            $mailResult = sendWelcomeEmail([
                'email'        => $email,
                'firstName'    => $firstName,
                'lastName'     => $lastName,
                'firma'        => $firma,
                'kundennummer' => $kundennummer,
                'personType'   => $personType
            ]);

            if (!$mailResult['success']) {
                error_log("Welcome email failed for $email: " . $mailResult['error']);
                // On NE bloque PAS l'inscription si l'email échoue
            }

        } catch (Throwable $mailEx) {
            error_log("Welcome email exception: " . $mailEx->getMessage());
            // Ne bloque jamais l'inscription
        }
        // ============================================================

        $response['success'] = true;
        $response['message'] = 'Registrierung erfolgreich!';
        $response['user'] = [
            'id'         => (int) $kundeId,
            'firstName'  => $firstName,
            'lastName'   => $lastName,
            'email'      => $email,
            'personType' => $personType,
            'phone'      => $phone,
            'firma'      => $firma ?? '',
            'kontakt'    => $kontakt ?? ''
        ];
    }

    // ============================================================
    // LOGIN
    // ============================================================
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['action'] ?? '') === 'login') {
        $email    = $input['email'] ?? '';
        $password = $input['password'] ?? '';

        if (empty($email) || empty($password)) {
            throw new Exception('Email und Passwort erforderlich.');
        }

        $stmt = $pdo->prepare("
            SELECT k.id, k.person_type, a.email, a.passwort_hash,
                   p.anrede, p.vorname, p.nachname,
                   g.firma, g.kontakt
            FROM kunden k
            JOIN kunden_authentifizierung a ON k.id = a.kunde_id
            LEFT JOIN kunden_personendaten p ON k.id = p.kunde_id
            LEFT JOIN kunden_gewerbedaten g ON k.id = g.kunde_id
            WHERE a.email = ?
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['passwort_hash'])) {
            throw new Exception('Email oder Passwort falsch.');
        }

        // Téléphone standard
        $stmtPhone = $pdo->prepare("SELECT wert FROM kunden_kommunikation WHERE kunde_id = ? AND typ = 'Telefon' AND ist_standard = 1");
        $stmtPhone->execute([$user['id']]);
        $phone = $stmtPhone->fetchColumn();
        if (!$phone) {
            $stmtPhone = $pdo->prepare("SELECT wert FROM kunden_kommunikation WHERE kunde_id = ? AND typ = 'Telefon' ORDER BY ist_standard DESC, id LIMIT 1");
            $stmtPhone->execute([$user['id']]);
            $phone = $stmtPhone->fetchColumn();
        }

        // Session
        $_SESSION['user_id']         = $user['id'];
        $_SESSION['kunde_id']        = $user['id'];
        $_SESSION['user_email']      = $user['email'];
        $_SESSION['user_first_name'] = $user['vorname'] ?? $user['kontakt'] ?? '';
        $_SESSION['user_last_name']  = $user['nachname'] ?? '';
        $_SESSION['person_type']     = $user['person_type'];

        $response['success'] = true;
        $response['message'] = 'Login erfolgreich.';
        $response['user'] = buildUserPayload($user, $phone ?: '');
    }

    // ============================================================
    // GET USER INFO
    // ============================================================
    elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'user') {
        if (empty($_SESSION['user_id'])) {
            throw new Exception('Nicht eingeloggt.');
        }
        $userId = $_SESSION['user_id'];

        $stmt = $pdo->prepare("
            SELECT k.id, k.person_type, a.email,
                   p.anrede, p.vorname, p.nachname,
                   g.firma, g.kontakt
            FROM kunden k
            JOIN kunden_authentifizierung a ON k.id = a.kunde_id
            LEFT JOIN kunden_personendaten p ON k.id = p.kunde_id
            LEFT JOIN kunden_gewerbedaten g ON k.id = g.kunde_id
            WHERE k.id = ?
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            throw new Exception('Benutzer nicht gefunden.');
        }

        $stmtPhone = $pdo->prepare("SELECT wert FROM kunden_kommunikation WHERE kunde_id = ? AND typ = 'Telefon' AND ist_standard = 1");
        $stmtPhone->execute([$userId]);
        $phone = $stmtPhone->fetchColumn();
        if (!$phone) {
            $stmtPhone = $pdo->prepare("SELECT wert FROM kunden_kommunikation WHERE kunde_id = ? AND typ = 'Telefon' ORDER BY ist_standard DESC, id LIMIT 1");
            $stmtPhone->execute([$userId]);
            $phone = $stmtPhone->fetchColumn();
        }

        $response['success'] = true;
        $response['user'] = buildUserPayload($user, $phone ?: '');
    }

    // ============================================================
    // GET FULL PROFILE
    // ============================================================
    elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'profile') {
        if (empty($_SESSION['user_id'])) {
            throw new Exception('Nicht eingeloggt.');
        }
        $userId = $_SESSION['user_id'];

        $stmt = $pdo->prepare("SELECT * FROM kunden WHERE id = ?");
        $stmt->execute([$userId]);
        $kunde = $stmt->fetch();
        if (!$kunde) {
            throw new Exception('Kunde nicht gefunden.');
        }

        $stmt = $pdo->prepare("SELECT id, email, agb_accepted, datenschutz_accepted, letzte_anmeldung FROM kunden_authentifizierung WHERE kunde_id = ?");
        $stmt->execute([$userId]);
        $auth = $stmt->fetch();

        $stmt = $pdo->prepare("SELECT * FROM kunden_personendaten WHERE kunde_id = ?");
        $stmt->execute([$userId]);
        $person = $stmt->fetch();

        $stmt = $pdo->prepare("SELECT * FROM kunden_gewerbedaten WHERE kunde_id = ?");
        $stmt->execute([$userId]);
        $gewerbe = $stmt->fetch() ?: null;

        $stmt = $pdo->prepare("SELECT * FROM kunden_identifikation WHERE kunde_id = ?");
        $stmt->execute([$userId]);
        $ident = $stmt->fetch();

        $stmt = $pdo->prepare("SELECT * FROM kunden_klassifizierung WHERE kunde_id = ?");
        $stmt->execute([$userId]);
        $klass = $stmt->fetch();

        $stmt = $pdo->prepare("SELECT * FROM kunden_adressen WHERE kunde_id = ? ORDER BY ist_standard DESC");
        $stmt->execute([$userId]);
        $addresses = $stmt->fetchAll();

        $stmt = $pdo->prepare("SELECT * FROM kunden_kommunikation WHERE kunde_id = ? ORDER BY ist_standard DESC");
        $stmt->execute([$userId]);
        $kommunikation = $stmt->fetchAll();

        $stmt = $pdo->prepare("SELECT * FROM kunden_bankverbindung WHERE kunde_id = ? ORDER BY ist_standard DESC");
        $stmt->execute([$userId]);
        $bank = $stmt->fetchAll();

        $stmt = $pdo->prepare("SELECT * FROM kunden_rechnungsdaten WHERE kunde_id = ?");
        $stmt->execute([$userId]);
        $rechnung = $stmt->fetch();

        $stmt = $pdo->prepare("SELECT * FROM kunden_preisgruppen WHERE kunde_id = ? ORDER BY prioritaet");
        $stmt->execute([$userId]);
        $preisgruppen = $stmt->fetchAll();

        $stmt = $pdo->prepare("SELECT * FROM kunden_artikelrabatte WHERE kunde_id = ? ORDER BY stufe");
        $stmt->execute([$userId]);
        $artikelrabatte = $stmt->fetchAll();

        $stmt = $pdo->prepare("SELECT * FROM kunden_mahngebuehren WHERE kunde_id = ? ORDER BY stufe");
        $stmt->execute([$userId]);
        $mahngebuehren = $stmt->fetchAll();

        $stmt = $pdo->prepare("SELECT * FROM kunden_skonto WHERE kunde_id = ? ORDER BY stufe");
        $stmt->execute([$userId]);
        $skonti = $stmt->fetchAll();

        $stmt = $pdo->prepare("SELECT * FROM kunden_bemerkungen WHERE kunde_id = ? ORDER BY erstellt_am DESC");
        $stmt->execute([$userId]);
        $bemerkungen = $stmt->fetchAll();

        $stmt = $pdo->prepare("SELECT * FROM kunden_zusatzfelder WHERE kunde_id = ?");
        $stmt->execute([$userId]);
        $zusatzfelder = $stmt->fetchAll();

        $response['success'] = true;
        $response['data'] = [
            'kunde'         => $kunde,
            'auth'          => $auth,
            'person'        => $person ?: null,
            'gewerbe'       => $gewerbe,
            'ident'         => $ident ?: null,
            'klass'         => $klass ?: null,
            'addresses'     => $addresses ?: [],
            'kommunikation' => $kommunikation ?: [],
            'bank'          => $bank ?: [],
            'rechnung'      => $rechnung ?: null,
            'preisgruppen'  => $preisgruppen ?: [],
            'artikelrabatte'=> $artikelrabatte ?: [],
            'mahngebuehren' => $mahngebuehren ?: [],
            'skonto'        => $skonti ?: [],
            'bemerkungen'   => $bemerkungen ?: [],
            'zusatzfelder'  => $zusatzfelder ?: []
        ];
    }

    // ============================================================
    // UPDATE PROFILE
    // ============================================================
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['action'] ?? '') === 'updateProfile') {
        require_once 'update_profile.php';
        exit;
    }

    // ============================================================
    // DELETE ADDRESS
    // ============================================================
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['action'] ?? '') === 'deleteAddress') {
        require_once 'delete_address.php';
        exit;
    }

    // ============================================================
    // DELETE COMMUNICATION
    // ============================================================
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['action'] ?? '') === 'deleteCommunication') {
        require_once 'delete_communication.php';
        exit;
    }

    // ============================================================
    // LOGOUT
    // ============================================================
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['action'] ?? '') === 'logout') {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        $response['success'] = true;
        $response['message'] = 'Logout erfolgreich.';
    }

    else {
        throw new Exception('Ungültige Aktion.');
    }

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    error_log("Auth API error: " . $e->getMessage());
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $response['success'] = false;
    $response['message'] = 'Serverfehler: ' . $e->getMessage();
    error_log("Auth API fatal: " . $e->getMessage());
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($response);
exit;