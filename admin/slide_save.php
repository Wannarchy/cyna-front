<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/admin_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: slides.php');
    exit;
}

if (empty($_POST) && empty($_FILES) && ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    header('Location: slides.php?error='.urlencode('Envoi du formulaire impossible (fichier trop lourd ?). Réduisez l\'image ou augmentez les limites PHP.'));
    exit;
}

$slideId = (int) ($_POST['id'] ?? 0);
$redirect = $slideId > 0 ? 'slides.php?edit='.$slideId : 'slides.php';
$currentImagePath = trim($_POST['current_image_path'] ?? '');
$newImagePath = null;
$imageSelected = admin_request_has_image_file($_FILES['image'] ?? null);

if ($imageSelected) {
    try {
        $newImagePath = admin_upload_product_image($_FILES['image'] ?? null, 'slides');
    } catch (Throwable $e) {
        header('Location: '.$redirect.'&error='.urlencode('Upload image : '.$e->getMessage()));
        exit;
    }

    if ($newImagePath === null || trim((string) $newImagePath) === '') {
        header('Location: '.$redirect.'&error='.urlencode('Upload image : aucun identifiant Cloudinary reçu.'));
        exit;
    }
}

$payload = admin_slide_payload(
    $_POST,
    $newImagePath,
    $currentImagePath !== '' ? $currentImagePath : null
);

if ($payload['title'] === '') {
    header('Location: slides.php?error='.urlencode('Le titre de la slide est obligatoire.'));
    exit;
}

try {
    admin_api()->adminUpdateSlides([$payload]);
} catch (RuntimeException $e) {
    header('Location: '.$redirect.'&error='.urlencode($e->getMessage()));
    exit;
}

$success = $slideId > 0 ? 'Slide mise à jour.' : 'Slide ajoutée.';
header('Location: slides.php?success='.urlencode($success));
exit;
