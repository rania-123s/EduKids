<?php
declare(strict_types=1);

require_once __DIR__ . '/../_helpers.php';
require_once __DIR__ . '/../db.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    json_response([
        'success' => false,
        'message' => 'ID manquant',
    ], 422);
}

$stmt = $pdo->prepare('SELECT id, user_id_id, parent_id, date, date_commande, montant_total, statut FROM commande WHERE id = :id');
$stmt->execute([':id' => (int) $id]);
$commande = $stmt->fetch();

if (!$commande) {
    json_response([
        'success' => false,
        'message' => 'Commande introuvable',
    ], 404);
}

$stmt = $pdo->prepare(
    'SELECT lc.id, lc.produit_id, lc.quantite, lc.prix, p.nom, p.type
     FROM ligne_commande lc
     LEFT JOIN produit p ON p.id = lc.produit_id
     WHERE lc.commande_id = :commande_id'
);
$stmt->execute([':commande_id' => (int) $id]);
$lignes = $stmt->fetchAll();

json_response([
    'success' => true,
    'data' => [
        'commande' => [
            'id' => (int) $commande['id'],
            'user_id' => (int) $commande['user_id_id'],
            'parent_id' => isset($commande['parent_id']) ? (int) $commande['parent_id'] : (int) $commande['user_id_id'],
            'date' => $commande['date'],
            'date_commande' => $commande['date_commande'] ?? $commande['date'],
            'montant_total' => (int) $commande['montant_total'],
            'statut' => $commande['statut'],
        ],
        'lignes' => $lignes,
    ],
]);
