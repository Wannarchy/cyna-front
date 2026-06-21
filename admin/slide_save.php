<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/admin_helpers.php';

$payload = admin_slide_payload($_POST);

if ($payload['title'] === '') {
    header('Location: slides.php');
    exit;
}

try {
    admin_api()->adminUpdateSlides([$payload]);
} catch (RuntimeException) {
}

header('Location: slides.php');
exit;
