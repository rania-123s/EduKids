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

$stmt = $pdo->prepare('SELECT id, nom, description, prix, type, age_min, image, statut, date_creation FROM produit WHERE id = :id');
$stmt->execute([':id' => (int) $id]);
$row = $stmt->fetch();

if (!$row) {
    json_response([
        'success' => false,
        'message' => 'Produit introuvable',
    ], 404);
}

json_response([
    'success' => true,
    'data' => [
        'id' => (int) $row['id'],
        'nom' => $row['nom'],
        'description' => $row['description'],
        'prix' => (int) $row['prix'],
        'type' => $row['type'],
        'age_min' => isset($row['age_min']) ? (int) $row['age_min'] : null,
        'image' => $row['image'],
        'statut' => $row['statut'],
        'date_creation' => $row['date_creation'],
    ],
]);
