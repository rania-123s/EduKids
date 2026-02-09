<?php
declare(strict_types=1);

require_once __DIR__ . '/../_helpers.php';
require_once __DIR__ . '/../db.php';

$stmt = $pdo->query('SELECT id, user_id_id, parent_id, date, date_commande, montant_total, statut FROM commande ORDER BY id DESC');
$rows = $stmt->fetchAll();

$items = array_map(function ($row) {
    return [
        'id' => (int) $row['id'],
        'user_id' => (int) $row['user_id_id'],
        'date' => $row['date'],
        'montant_total' => (int) $row['montant_total'],
        'statut' => $row['statut'],
        'parent_id' => isset($row['parent_id']) ? (int) $row['parent_id'] : (int) $row['user_id_id'],
        'date_commande' => $row['date_commande'] ?? $row['date'],
    ];
}, $rows);

json_response(['success' => true, 'data' => $items]);
