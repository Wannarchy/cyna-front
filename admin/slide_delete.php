<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/admin_helpers.php';

$id = (int) ($_GET['id'] ?? 0);

if ($id > 0) {
    try {
        admin_api()->adminDeleteSlide($id);
    } catch (RuntimeException) {
    }
}

header('Location: slides.php');
exit;
