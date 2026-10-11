<?php
// api/fix_uids.php
// ⚠️ FICHIER TEMPORAIRE — À SUPPRIMER APRÈS USAGE
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/database_connect.php';

if (!$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB connexion échouée']);
    exit;
}

function generateUuidV4(): string {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

try {
    // Récupère tous les users avec uid vide
    $stmt = $pdo->query("SELECT id, email, uid FROM users WHERE uid IS NULL OR uid = ''");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $fixed = [];

    foreach ($users as $u) {
        $uid = generateUuidV4();
        $upd = $pdo->prepare("UPDATE users SET uid = ? WHERE id = ?");
        $upd->execute([$uid, $u['id']]);

        $fixed[] = [
            'id'    => (int)$u['id'],
            'email' => $u['email'],
            'uid'   => $uid
        ];
    }

    echo json_encode([
        'success' => true,
        'message' => count($fixed) . ' utilisateur(s) corrigé(s)',
        'fixed'   => $fixed,
        'info'    => '⚠️ Supprime ce fichier après usage !'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
}