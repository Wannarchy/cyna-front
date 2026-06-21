<?php

require_once __DIR__ . '/../config/config.php';
cyna_session_start();
require_once __DIR__ . '/../includes/home_repository.php';
require_once __DIR__ . '/../includes/cart_repository.php';

$product_id = (int) ($_POST['product_id'] ?? $_GET['product_id'] ?? 0);
$cycle = (($_POST['cycle'] ?? $_GET['cycle'] ?? 'monthly') === 'yearly') ? 'yearly' : 'monthly';

if ($product_id > 0) {
    try {
        $product = api_client()->getProduct($product_id);
    } catch (Throwable) {
        $product = null;
    }

    if ($product && product_is_available($product)) {
        if (! isset($_SESSION['cart']) || ! is_array($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        $maxQty = cart_max_quantity($product);
        if (isset($_SESSION['cart'][$product_id])) {
            $currentQty = (int) ($_SESSION['cart'][$product_id]['qty'] ?? 1);
            $_SESSION['cart'][$product_id]['cycle'] = $cycle;
            $_SESSION['cart'][$product_id]['qty'] = min($currentQty + 1, $maxQty);
        } else {
            $_SESSION['cart'][$product_id] = [
                'cycle' => $cycle,
                'qty' => 1,
            ];
        }
    }
}

header('Location: panier.php');
exit;
