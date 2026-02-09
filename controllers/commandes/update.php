<?php
declare(strict_types=1);

require_once __DIR__ . '/../_helpers.php';
require_once __DIR__ . '/../db.php';

$data = request_data();
$id = $data['id'] ?? ($_GET['id'] ?? null);

if (!$id) {
    json_response([
        'success' => false,
        'message' => 'ID manquant',
    ], 422);
}

$stmt = $pdo->prepare('SELECT id FROM commande WHERE id = :id');
$stmt->execute([':id' => (int) $id]);
if (!$stmt->fetch()) {
    json_response([
        'success' => false,
        'message' => 'Commande introuvable',
    ], 404);
}

$stmt = $pdo->prepare(
    'UPDATE commande
     SET date = COALESCE(:date, date),
         date_commande = COALESCE(:date_commande, date_commande),
         montant_total = COALESCE(:montant_total, montant_total),
         statut = COALESCE(:statut, statut),
         user_id_id = COALESCE(:user_id_id, user_id_id),
         parent_id = COALESCE(:parent_id, parent_id)
     WHERE id = :id'
);
$stmt->execute([
    ':date' => $data['date'] ?? null,
    ':date_commande' => $data['date_commande'] ?? ($data['date'] ?? null),
    ':montant_total' => isset($data['montant_total']) ? (int) $data['montant_total'] : null,
    ':statut' => $data['statut'] ?? null,
    ':user_id_id' => isset($data['user_id']) ? (int) $data['user_id'] : null,
    ':parent_id' => isset($data['user_id']) ? (int) $data['user_id'] : null,
    ':id' => (int) $id,
]);

$stmt = $pdo->prepare('SELECT id, user_id_id, parent_id, date, date_commande, montant_total, statut FROM commande WHERE id = :id');
$stmt->execute([':id' => (int) $id]);
$row = $stmt->fetch();

json_response([
    'success' => true,
    'data' => [
        'id' => (int) $row['id'],
        'user_id' => (int) $row['user_id_id'],
        'date' => $row['date'],
        'montant_total' => (int) $row['montant_total'],
        'statut' => $row['statut'],
        'parent_id' => isset($row['parent_id']) ? (int) $row['parent_id'] : (int) $row['user_id_id'],
        'date_commande' => $row['date_commande'] ?? $row['date'],
    ],
]);
