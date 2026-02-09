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

$stmt = $pdo->prepare('DELETE FROM commande WHERE id = :id');
$stmt->execute([':id' => (int) $id]);

if ($stmt->rowCount() === 0) {
    json_response([
        'success' => false,
        'message' => 'Commande introuvable',
    ], 404);
}

json_response(['success' => true]);
