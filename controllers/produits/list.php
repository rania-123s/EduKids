<?php
declare(strict_types=1);

require_once __DIR__ . '/../_helpers.php';
require_once __DIR__ . '/../db.php';

$stmt = $pdo->query('SELECT id, nom, description, prix, type, age_min, image, statut, date_creation FROM produit ORDER BY id DESC');
$rows = $stmt->fetchAll();

$items = array_map(function ($row) {
    return [
        'id' => (int) $row['id'],
        'nom' => $row['nom'],
        'description' => $row['description'],
        'prix' => (int) $row['prix'],
        'type' => $row['type'],
        'age_min' => isset($row['age_min']) ? (int) $row['age_min'] : null,
        'image' => $row['image'],
        'statut' => $row['statut'],
        'date_creation' => $row['date_creation'],
    ];
}, $rows);

json_response(['success' => true, 'data' => $items]);
