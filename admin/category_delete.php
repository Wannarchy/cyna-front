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
    header('Location: categories.php?error='.urlencode('Aucune catégorie sélectionnée.'));
    exit;
}

$deleted = 0;
$errors = [];

foreach ($ids as $id) {
    try {
        admin_api()->adminDeleteCategory($id);
        $deleted++;
    } catch (Throwable $e) {
        $errors[] = $e->getMessage();
    }
}

if ($deleted > 0 && $errors === []) {
    $message = count($ids) === 1
        ? 'Catégorie supprimée.'
        : $deleted.' catégorie(s) supprimée(s).';
    header('Location: categories.php?success='.urlencode($message));
    exit;
}

if ($deleted > 0 && $errors !== []) {
    header('Location: categories.php?success='.urlencode($deleted.' catégorie(s) supprimée(s).').'&warn='.urlencode('Certaines suppressions ont échoué : '.implode(' ', array_unique($errors))));
    exit;
}

header('Location: categories.php?error='.urlencode('Suppression impossible : '.implode(' ', array_unique($errors))));
exit;
