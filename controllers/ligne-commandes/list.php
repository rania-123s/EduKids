<?php
declare(strict_types=1);

require_once __DIR__ . '/../_helpers.php';
require_once __DIR__ . '/../db.php';

$commandeId = $_GET['commande_id'] ?? null;

if (!$commandeId) {
    json_response([
        'success' => false,
        'message' => 'commande_id manquant',
    ], 422);
}

$stmt = $pdo->prepare(
    'SELECT lc.id, lc.commande_id, lc.produit_id, lc.quantite, lc.prix,
            p.nom, p.type
     FROM ligne_commande lc
     LEFT JOIN produit p ON p.id = lc.produit_id
     WHERE lc.commande_id = :commande_id'
);
$stmt->execute([':commande_id' => (int) $commandeId]);
$rows = $stmt->fetchAll();

json_response([
    'success' => true,
    'data' => $rows,
]);
