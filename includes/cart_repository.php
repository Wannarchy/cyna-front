<?php

require_once __DIR__ . '/home_repository.php';

function cart_max_quantity(array $product): int
{
    if (! product_is_available($product)) {
        return 0;
    }

    if (array_key_exists('stock', $product)) {
        return max(1, (int) $product['stock']);
    }

    return 99;
}

function cart_session_count(): int
{
    $cart = $_SESSION['cart'] ?? [];

    if ($cart === []) {
        return 0;
    }

    return (int) array_sum(array_column($cart, 'qty'));
}

function cart_session_distinct_count(): int
{
    $cart = $_SESSION['cart'] ?? [];

    return is_array($cart) ? count($cart) : 0;
}

function cart_set_quantity(array $cart, int $productId, int $qty): array
{
    if (! isset($cart[$productId])) {
        return $cart;
    }

    try {
        $product = api_client()->getProduct($productId);
    } catch (Throwable) {
        return $cart;
    }

    if (! $product || ! product_is_available($product)) {
        unset($cart[$productId]);

        return $cart;
    }

    $max = cart_max_quantity($product);
    $cart[$productId]['qty'] = max(1, min($qty, $max));

    return $cart;
}

function cart_get_products($db, array $cart): array
{
    if (empty($cart)) {
        return [];
    }

    $result = [];

    foreach ($cart as $productId => $meta) {
        try {
            $product = api_client()->getProduct((int) $productId);
        } catch (Throwable) {
            continue;
        }

        if (! $product) {
            continue;
        }

        $cycle = $meta['cycle'] ?? 'monthly';
        $qty = max(1, (int) ($meta['qty'] ?? 1));
        $maxQty = cart_max_quantity($product);

        if ($maxQty <= 0) {
            $qty = 1;
        } else {
            $qty = min($qty, $maxQty);
        }

        $price = $cycle === 'yearly'
            ? (float) $product['price_yearly']
            : (float) $product['price_monthly'];

        $lineTotal = round($price * $qty, 2);

        $result[] = [
            'id' => (int) $product['id'],
            'name' => $product['name'],
            'image_path' => $product['image_path'] ?? '',
            'cycle' => $cycle,
            'qty' => $qty,
            'max_qty' => $maxQty,
            'price_monthly' => (float) $product['price_monthly'],
            'price_yearly' => (float) $product['price_yearly'],
            'unit_price' => $price,
            'line_total' => $lineTotal,
            'is_available' => product_is_available($product),
            'stock' => (int) ($product['stock'] ?? 0),
            'requires_shipping' => ! empty($product['requires_shipping']),
        ];
    }

    return $result;
}

function cart_total(array $items): float
{
    $total = 0;
    foreach ($items as $it) {
        if ($it['is_available']) {
            $total += (float) ($it['line_total'] ?? $it['unit_price']);
        }
    }

    return round($total, 2);
}

function cart_build_order_items(array $items): array
{
    $orderItems = [];

    foreach ($items as $item) {
        if (! $item['is_available']) {
            continue;
        }

        $qty = max(1, (int) ($item['qty'] ?? 1));
        for ($i = 0; $i < $qty; $i++) {
            $orderItems[] = [
                'product_id' => $item['id'],
                'cycle' => $item['cycle'],
            ];
        }
    }

    return $orderItems;
}
