<?php

require_once __DIR__ . '/../config/config.php';
cyna_session_start();

require_once __DIR__ . '/../includes/cart_repository.php';
require_once __DIR__ . '/../includes/address_helpers.php';

if (! isset($_SESSION['utilisateur_id'])) {
    header('Location: connexion.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: panier.php');
    exit;
}

$cart = $_SESSION['cart'] ?? [];
$items = cart_get_products($connexion, $cart);

if (count($items) === 0) {
    header('Location: panier.php');
    exit;
}

$address = address_from_input($_POST);
$payment_method = trim($_POST['payment_method'] ?? $_POST['stripe_token'] ?? '');
$promo_code = trim($_POST['promo_code'] ?? '');
$needs_shipping = cart_requires_shipping($items);
$shipping_same = ! empty($_POST['shipping_same_as_billing']);

$errors = array_merge([], address_validate_required($address));

if ($needs_shipping && ! $shipping_same) {
    $ship = address_from_input($_POST, 'shipping_');
    $errors = array_merge($errors, address_validate_required($ship));
} else {
    $ship = $address;
}

if (empty($payment_method)) {
    $errors[] = 'Moyen de paiement Stripe requis.';
}

if (! empty($errors)) {
    $_SESSION['checkout_errors'] = $errors;
    header('Location: checkout.php');
    exit;
}

$billing_name = address_billing_name($address);
$billing_address = address_format_multiline($address);
$shipping_name = $needs_shipping ? address_billing_name($ship) : null;
$shipping_address = $needs_shipping ? address_format_multiline($ship) : null;

$orderItems = cart_build_order_items($items);

$payload = [
    'billing_name' => $billing_name,
    'billing_address' => $billing_address,
    'payment_method' => $payment_method,
    'promo_code' => $promo_code !== '' ? $promo_code : null,
    'items' => $orderItems,
];

if ($needs_shipping) {
    $payload['shipping_name'] = $shipping_name;
    $payload['shipping_address'] = $shipping_address;
}

try {
    $order = api_client()->createOrder($payload);

    unset($_SESSION['cart']);
    header('Location: confirmation.php?order_id='.(int) ($order['id'] ?? 0));
    exit;
} catch (Throwable $e) {
    $_SESSION['checkout_errors'] = [$e->getMessage()];
    header('Location: checkout.php');
    exit;
}
