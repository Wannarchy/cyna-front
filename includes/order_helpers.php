<?php

function order_money(float $amount): string
{
    return number_format($amount, 2, ',', ' ').' €';
}

function order_cycle_label(string $cycle, string $lang = 'fr'): string
{
    if ($cycle === 'yearly') {
        return match ($lang) {
            'en' => 'Annual subscription',
            'ar' => 'اشتراك سنوي',
            'he' => 'מנוי שנתי',
            default => 'Abonnement annuel',
        };
    }

    return match ($lang) {
        'en' => 'Monthly subscription',
        'ar' => 'اشتراك شهري',
        'he' => 'מנוי חודשי',
        default => 'Abonnement mensuel',
    };
}

function order_payment_brand_label(?string $brand): string
{
    $brand = strtolower(trim((string) $brand));

    return match ($brand) {
        'visa' => 'Visa',
        'mastercard' => 'Mastercard',
        'amex', 'american express' => 'American Express',
        'discover' => 'Discover',
        default => $brand !== '' ? ucfirst($brand) : 'Carte bancaire',
    };
}

/**
 * @param array<string, mixed> $order
 * @return array{subtotal:float,tax_amount:float,promo_discount:float,subtotal_ht:float,total:float,promo_code:?string}
 */
function order_summary_amounts(array $order): array
{
    $items = $order['items'] ?? [];
    $total = (float) ($order['total'] ?? 0);
    $subtotal = (float) ($order['subtotal'] ?? 0);

    if ($subtotal <= 0 && $items !== []) {
        $subtotal = array_sum(array_map(static fn (array $item): float => (float) ($item['price'] ?? 0), $items));
    }

    $promoDiscount = (float) ($order['promo_discount'] ?? max(0, $subtotal - $total));
    $taxAmount = (float) ($order['tax_amount'] ?? 0);

    if ($taxAmount <= 0 && $total > 0) {
        $taxAmount = round($total - ($total / 1.2), 2);
    }

    $subtotalHt = (float) ($order['subtotal_ht'] ?? round($total - $taxAmount, 2));

    return [
        'subtotal' => round($subtotal, 2),
        'tax_amount' => round($taxAmount, 2),
        'promo_discount' => round($promoDiscount, 2),
        'subtotal_ht' => round($subtotalHt, 2),
        'total' => round($total, 2),
        'promo_code' => ! empty($order['promo_code']) ? (string) $order['promo_code'] : null,
    ];
}

function order_payment_display(array $order): string
{
    $brand = order_payment_brand_label($order['payment_brand'] ?? null);
    $last4 = trim((string) ($order['card_last4'] ?? ''));

    if ($last4 !== '') {
        return $brand.' •••• '.$last4;
    }

    return $brand;
}
