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

$stmt = $pdo->prepare('SELECT id FROM produit WHERE id = :id');
$stmt->execute([':id' => (int) $id]);
if (!$stmt->fetch()) {
    json_response([
        'success' => false,
        'message' => 'Produit introuvable',
    ], 404);
}

$imagePath = null;
if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
    $fileName = $_FILES['image_file']['name'];
    if (!preg_match('/\.(jpg|jpeg|png)$/i', $fileName)) {
        json_response([
            'success' => false,
            'message' => 'Image invalide (jpg/png)',
        ], 422);
    }
    $uploadDir = __DIR__ . '/../../assets/images/produits';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    $ext = pathinfo($fileName, PATHINFO_EXTENSION);
    $newName = uniqid('prod_', true) . '.' . $ext;
    $dest = $uploadDir . '/' . $newName;
    if (!move_uploaded_file($_FILES['image_file']['tmp_name'], $dest)) {
        json_response([
            'success' => false,
            'message' => 'Upload image échoué',
        ], 500);
    }
    $imagePath = 'assets/images/produits/' . $newName;
} else {
    $current = isset($data['image_current']) ? trim((string) $data['image_current']) : '';
    $imagePath = $current !== '' ? $current : null;
}

$stmt = $pdo->prepare(
    'UPDATE produit
     SET nom = COALESCE(:nom, nom),
         description = COALESCE(:description, description),
         prix = COALESCE(:prix, prix),
         type = COALESCE(:type, type),
         age_min = COALESCE(:age_min, age_min),
         image = COALESCE(:image, image),
         statut = COALESCE(:statut, statut)
     WHERE id = :id'
);
$stmt->execute([
    ':nom' => $data['nom'] ?? null,
    ':description' => $data['description'] ?? null,
    ':prix' => isset($data['prix']) ? (int) $data['prix'] : null,
    ':type' => $data['type'] ?? null,
    ':age_min' => isset($data['age_min']) && $data['age_min'] !== '' ? (int) $data['age_min'] : null,
    ':image' => $imagePath,
    ':statut' => $data['statut'] ?? null,
    ':id' => (int) $id,
]);

$stmt = $pdo->prepare('SELECT id, nom, description, prix, type, age_min, image, statut, date_creation FROM produit WHERE id = :id');
$stmt->execute([':id' => (int) $id]);
$row = $stmt->fetch();

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
