<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/admin_helpers.php';

$ids = [];

if (isset($_POST['ids']) && is_array($_POST['ids'])) {
    foreach ($_POST['ids'] as $rawId) {
        $id = (int) $rawId;
        if ($id > 0) {
            $ids[] = $id;
        }
    }
} else {
    $id = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
    if ($id > 0) {
        $ids[] = $id;
    }
}

$ids = array_values(array_unique($ids));

if ($ids === []) {
    header('Location: products.php?error='.urlencode('Aucun produit sélectionné.'));
    exit;
}

$deleted = 0;
$errors = [];

foreach ($ids as $id) {
    try {
        admin_api()->adminDeleteProduct($id);
        $deleted++;
    } catch (Throwable $e) {
        $errors[] = $e->getMessage();
    }
}

if ($deleted > 0 && $errors === []) {
    $message = count($ids) === 1
        ? 'Produit supprimé avec succès.'
        : $deleted.' produit(s) supprimé(s) avec succès.';
    header('Location: products.php?success='.urlencode($message));
    exit;
}

if ($deleted > 0 && $errors !== []) {
    header('Location: products.php?success='.urlencode($deleted.' produit(s) supprimé(s).').'&warn='.urlencode('Certaines suppressions ont échoué : '.implode(' ', array_unique($errors))));
    exit;
}

header('Location: products.php?error='.urlencode('Suppression impossible : '.implode(' ', array_unique($errors))));
exit;
