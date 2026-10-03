<?php
// api/delete_product.php
require_once __DIR__ . '/permission_helper.php';
applyCors();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/database_connect.php';

$dbh = $pdo;

$input = json_decode(file_get_contents('php://input'), true);
$productId = (int)($input['product_id'] ?? 0);

if (!$productId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'product_id erforderlich']);
    exit;
}

// ============================================================
// Helper : log verlauf
// ============================================================
function addVerlauf(PDO $dbh, ?int $artikelId, ?string $artikelNummer, string $aktion, ?string $feld = null, ?string $alter = null, ?string $neuer = null, ?string $beschreibung = null): void {
    try {
        $stmt = $dbh->prepare("
            INSERT INTO artikel_verlauf
                (artikel_id, artikel_nummer, benutzer, aktion, feld, alter_wert, neuer_wert, beschreibung)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $benutzer = $_SERVER['PHP_AUTH_USER'] ?? ($_SERVER['REMOTE_USER'] ?? 'System');
        $stmt->execute([$artikelId, $artikelNummer, $benutzer, $aktion, $feld, $alter, $neuer, $beschreibung]);
    } catch (Exception $e) {
        error_log('addVerlauf failed: ' . $e->getMessage());
    }
}

try {
    $dbh->beginTransaction();

    // ============================================================
    // 1. Charger le produit (pour le log et les contrôles)
    // ============================================================
    $stmt = $dbh->prepare("SELECT id, artikelnummer, bezeichnung, marke FROM artikel WHERE id = ?");
    $stmt->execute([$productId]);
    $produkt = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$produkt) {
        throw new Exception('Produkt nicht gefunden');
    }

    // ============================================================
    // 2. Contrôles : produit utilisé dans des commandes ?
    // ============================================================
    // (optionnel, décommenter si tu veux bloquer la suppression
    //  d'un produit qui apparaît dans une commande existante)
    /*
    $check = $dbh->prepare("
        SELECT COUNT(*) FROM order_items WHERE article_number = ?
    ");
    $check->execute([$produkt['artikelnummer']]);
    if ((int)$check->fetchColumn() > 0) {
        throw new Exception(
            'Dieses Produkt ist in Bestellungen referenziert und kann nicht gelöscht werden. ' .
            'Setzen Sie es stattdessen auf "nicht lagernd".'
        );
    }
    */

    // ============================================================
    // 3. Logger AVANT la suppression
    //    → grâce à artikel_id nullable + artikel_nummer, le log
    //      survit à la suppression du produit (si la FK a été
    //      retirée, cf. note en haut).
    // ============================================================
    addVerlauf(
        $dbh,
        $produkt['id'],
        $produkt['artikelnummer'],
        'delete',
        null,
        null,
        null,
        "Produkt gelöscht: {$produkt['bezeichnung']} ({$produkt['artikelnummer']})"
    );

    // ============================================================
    // 4. Suppression
    //    Les tables artikel_bilder et versandregeln ont une FK
    //    ON DELETE CASCADE → elles sont nettoyées automatiquement.
    // ============================================================
    $del = $dbh->prepare("DELETE FROM artikel WHERE id = ?");
    $del->execute([$productId]);

    if ($del->rowCount() === 0) {
        throw new Exception('Löschen fehlgeschlagen');
    }

    // ============================================================
    // 5. (Optionnel) Nettoyer les logs dupliqués de la FK CASCADE
    //    Si tu as enlevé la FK, ceci n'est PAS nécessaire.
    //    Sinon, on ne fait rien : les logs seront supprimés par
    //    la CASCADE. C'est justement pour ça qu'on retire la FK.
    // ============================================================

    $dbh->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Produkt gelöscht',
        'deleted_id' => $productId,
        'artikelnummer' => $produkt['artikelnummer']
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    if ($dbh->inTransaction()) $dbh->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'DB error: ' . $e->getMessage()]);
} catch (Exception $e) {
    if ($dbh->inTransaction()) $dbh->rollBack();
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}