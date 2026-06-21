<?php

http_response_code(410);
header('Content-Type: application/json');
echo json_encode([
    'error' => 'gone',
    'message' => 'Les webhooks Stripe sont gérés par l\'API Laravel (/stripe/webhook). Mettez à jour la configuration Stripe.',
]);
exit;
