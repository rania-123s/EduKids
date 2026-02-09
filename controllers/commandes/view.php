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
$row = $stmt->fetch();

if (!$row) {
    json_response([
        'success' => false,
        'message' => 'Commande introuvable',
    ], 404);
}

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
