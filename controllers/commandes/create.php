<?php
declare(strict_types=1);

require_once __DIR__ . '/../_helpers.php';
require_once __DIR__ . '/../db.php';

$data = request_data();
$required = ['user_id', 'date', 'montant_total', 'statut'];

foreach ($required as $field) {
    if (!isset($data[$field]) || $data[$field] === '') {
        json_response([
            'success' => false,
            'message' => "Champ manquant: {$field}",
        ], 422);
    }
}

$stmt = $pdo->prepare(
    'INSERT INTO commande (date, montant_total, statut, user_id_id, parent_id, date_commande)
     VALUES (:date, :montant_total, :statut, :user_id_id, :parent_id, :date_commande)'
);
$stmt->execute([
    ':date' => (string) $data['date'],
    ':montant_total' => (int) $data['montant_total'],
    ':statut' => (string) $data['statut'],
    ':user_id_id' => (int) $data['user_id'],
    ':parent_id' => (int) $data['user_id'],
    ':date_commande' => (string) $data['date'],
]);

$id = (int) $pdo->lastInsertId();
$item = [
    'id' => $id,
    'user_id' => (int) $data['user_id'],
    'date' => (string) $data['date'],
    'montant_total' => (int) $data['montant_total'],
    'statut' => (string) $data['statut'],
    'parent_id' => (int) $data['user_id'],
    'date_commande' => (string) $data['date'],
];

json_response(['success' => true, 'data' => $item]);
