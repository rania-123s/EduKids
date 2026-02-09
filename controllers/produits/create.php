<?php
declare(strict_types=1);

require_once __DIR__ . '/../_helpers.php';
require_once __DIR__ . '/../db.php';

$data = request_data();
$required = ['nom', 'description', 'prix', 'type'];

foreach ($required as $field) {
    if (!isset($data[$field]) || $data[$field] === '') {
        json_response([
            'success' => false,
            'message' => "Champ manquant: {$field}",
        ], 422);
    }
}

$ageMin = isset($data['age_min']) && $data['age_min'] !== '' ? (int) $data['age_min'] : null;
$statut = isset($data['statut']) && $data['statut'] !== '' ? (string) $data['statut'] : 'actif';

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
}

$stmt = $pdo->prepare(
    'INSERT INTO produit (nom, description, prix, type, age_min, image, statut, date_creation)
     VALUES (:nom, :description, :prix, :type, :age_min, :image, :statut, NOW())'
);
$stmt->execute([
    ':nom' => (string) $data['nom'],
    ':description' => (string) $data['description'],
    ':prix' => (int) $data['prix'],
    ':type' => (string) $data['type'],
    ':age_min' => $ageMin,
    ':image' => $imagePath,
    ':statut' => $statut,
]);

$id = (int) $pdo->lastInsertId();
$item = [
    'id' => $id,
    'nom' => (string) $data['nom'],
    'description' => (string) $data['description'],
    'prix' => (int) $data['prix'],
    'type' => (string) $data['type'],
    'age_min' => $ageMin,
    'image' => $imagePath,
    'statut' => $statut,
];

json_response(['success' => true, 'data' => $item]);
