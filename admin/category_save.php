<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/admin_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: categories.php');
    exit;
}

if (empty($_POST) && empty($_FILES) && ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    header('Location: categories.php?error='.urlencode('Envoi du formulaire impossible (fichier trop lourd ?). Réduisez l\'image ou augmentez post_max_size / upload_max_filesize dans php.ini.'));
    exit;
}

$id = (int) ($_POST['id'] ?? 0);
if ($id > 0) {
    header('Location: category_edit.php?id='.$id);
    exit;
}

$name = trim($_POST['name'] ?? '');
$sortOrder = (int) ($_POST['sort_order'] ?? 0);

$imageFile = $_FILES['image'] ?? null;
$imageSelected = admin_request_has_image_file($imageFile);
$newImagePath = null;

if ($imageSelected) {
    try {
        $newImagePath = admin_upload_product_image($imageFile, 'categories');
    } catch (Throwable $e) {
        header('Location: categories.php?error='.urlencode('Upload image impossible : '.$e->getMessage()));
        exit;
    }

    if ($newImagePath === null || trim($newImagePath) === '') {
        header('Location: categories.php?error='.urlencode('Upload image impossible : aucune URL Cloudinary reçue.'));
        exit;
    }
}

if ($name === '') {
    header('Location: categories.php?error='.urlencode('Le nom de la catégorie est obligatoire.'));
    exit;
}

if ($sortOrder < 1) {
    header('Location: categories.php?error='.urlencode('L\'ordre d\'affichage doit être au minimum 1.'));
    exit;
}

if (admin_category_sort_order_taken($sortOrder)) {
    header('Location: categories.php?error='.urlencode('Cet ordre d\'affichage est déjà utilisé par une autre catégorie.'));
    exit;
}

try {
    admin_api()->adminCreateCategory(admin_category_payload($_POST, $newImagePath));
    header('Location: categories.php?success='.urlencode('Catégorie « '.$name.' » créée avec succès.'));
    exit;
} catch (Throwable $e) {
    header('Location: categories.php?error='.urlencode('Création impossible : '.$e->getMessage()));
    exit;
}
