<?php
declare(strict_types=1);

require_once __DIR__ . '/../_helpers.php';
require_once __DIR__ . '/../db.php';

$data = request_data();
$required = ['commande_id', 'produit_id', 'quantite', 'prix'];

foreach ($required as $field) {
    if (!isset($data[$field]) || $data[$field] === '') {
        json_response([
            'success' => false,
            'message' => "Champ manquant: {$field}",
        ], 422);
    }
}

$stmt = $pdo->prepare(
    'INSERT INTO ligne_commande (commande_id, produit_id, quantite, prix)
     VALUES (:commande_id, :produit_id, :quantite, :prix)'
);
$stmt->execute([
    ':commande_id' => (int) $data['commande_id'],
    ':produit_id' => (int) $data['produit_id'],
    ':quantite' => (int) $data['quantite'],
    ':prix' => (int) $data['prix'],
]);

$id = (int) $pdo->lastInsertId();

json_response([
    'success' => true,
    'data' => [
        'id' => $id,
        'commande_id' => (int) $data['commande_id'],
        'produit_id' => (int) $data['produit_id'],
        'quantite' => (int) $data['quantite'],
        'prix' => (int) $data['prix'],
    ],
]);
