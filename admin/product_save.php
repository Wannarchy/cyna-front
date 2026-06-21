<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/admin_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: products.php');
    exit;
}

if (empty($_POST) && empty($_FILES)) {
    header('Location: products.php?error='.urlencode('Envoi du formulaire impossible (fichier trop lourd ?). Réduisez l\'image ou augmentez post_max_size / upload_max_filesize dans php.ini.'));
    exit;
}

$name = trim($_POST['name'] ?? '');

if ($name === '') {
    header('Location: products.php?error='.urlencode('Le nom du produit est obligatoire.'));
    exit;
}

if (admin_product_name_taken($name)) {
    header('Location: products.php?error='.urlencode('Un produit avec ce nom existe déjà.'));
    exit;
}

if (admin_product_category_id($_POST) <= 0) {
    header('Location: products.php?error='.urlencode('La catégorie est obligatoire.'));
    exit;
}

$imageFile = $_FILES['image'] ?? null;
$imageSelected = admin_request_has_image_file($imageFile);
$newImagePath = null;

try {
    $newImagePath = admin_upload_product_image($imageFile, 'products');
} catch (Throwable $e) {
    header('Location: products.php?error='.urlencode('Upload image impossible : '.$e->getMessage()));
    exit;
}

if ($imageSelected && ($newImagePath === null || trim($newImagePath) === '')) {
    header('Location: products.php?error='.urlencode('Upload image impossible : aucune URL Cloudinary reçue.'));
    exit;
}

try {
    $result = admin_api()->adminCreateProduct(admin_product_payload($_POST, $newImagePath));
    $created = $result['data'] ?? [];
    $apiMessage = trim((string) ($result['message'] ?? ''));

    if (empty($created['id'])) {
        throw new RuntimeException('Le produit n\'a pas été enregistré (réponse API vide).');
    }

    $query = ['success' => 'Produit « '.$name.' » créé. Complétez le carrousel et la fiche ci-dessous.'];
    if ($apiMessage !== '' && stripos($apiMessage, 'Stripe') !== false && stripos($apiMessage, 'échouée') !== false) {
        $query['warn'] = $apiMessage;
    }

    header('Location: product_edit.php?id='.(int)$created['id'].'&'.http_build_query($query));
    exit;
} catch (Throwable $e) {
    header('Location: products.php?error='.urlencode('Création impossible : '.$e->getMessage()));
    exit;
}
