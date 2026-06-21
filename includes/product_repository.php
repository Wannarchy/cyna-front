<?php

require_once __DIR__ . '/home_repository.php';

function product_get_by_id($db, int $id): ?array
{
    try {
        $product = api_client()->getProduct($id);
    } catch (Throwable) {
        return null;
    }

    if (! $product) {
        return null;
    }

    $available = product_is_available($product) ? 1 : 0;
    $specs = $product['technical_specs'] ?? [];
    if (! is_array($specs)) {
        $specs = [];
    }

    return [
        'id' => $product['id'],
        'category_id' => $product['category_id'] ?? null,
        'name' => $product['name'],
        'description' => trim((string) ($product['description'] ?? '')),
        'technical_specs' => array_values(array_filter(array_map('strval', $specs))),
        'image_path' => $product['image_path'] ?? 'logo.jpg',
        'price_monthly' => $product['price_monthly'],
        'price_yearly' => $product['price_yearly'],
        'is_available' => $available,
        'is_available_flag' => ! empty($product['is_available']),
        'stock' => array_key_exists('stock', $product) ? (int) $product['stock'] : null,
        'category_name' => $product['category']['name'] ?? null,
    ];
}

function product_get_similar($db, ?int $category_id, int $exclude_id, int $limit = 6): array
{
    $query = ['is_featured' => 'true'];
    if ($category_id) {
        $query['category_id'] = $category_id;
    }

    try {
        $products = api_client()->getProducts($query);
    } catch (Throwable) {
        return [];
    }

    $products = array_values(array_filter($products, fn ($p) => (int) ($p['id'] ?? 0) !== $exclude_id));

    return array_map('product_map_legacy', array_slice($products, 0, $limit));
}

/**
 * @return list<array{image_path:string,caption:string}>
 */
function product_gallery_slides(array $product): array
{
    $mainImage = $product['image_path'] ?? '';
    if ($mainImage === '' || in_array($mainImage, ['logo.jpg', 'logo.png'], true)) {
        return [];
    }

    return [
        [
            'image_path' => $mainImage,
            'caption' => '',
        ],
    ];
}

function product_desc_fallback(string $name, string $description = ''): string
{
    if (trim($description) !== '') {
        return trim($description);
    }

    return 'Solution SaaS CYNA : '.$name.'. Déploiement rapide, supervision en temps réel, alertes et conformité renforcée.';
}

function product_specs_fallback(array $specs): array
{
    if ($specs !== []) {
        return $specs;
    }

    return [
        'Supervision et alertes en temps réel',
        'Tableaux de bord & reporting',
        'Conformité & journalisation',
        'Support et SLA selon l’offre',
        'Déploiement rapide (SaaS)',
    ];
}
