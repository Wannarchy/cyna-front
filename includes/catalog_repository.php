<?php

require_once __DIR__ . '/home_repository.php';

function products_get_by_category($db, int $category_id, int $limit = 12, int $offset = 0): array
{
    try {
        $products = api_client()->getProducts(['category_id' => $category_id]);
    } catch (Throwable) {
        return [];
    }

    $products = array_slice($products, $offset, $limit);

    return array_map(static function (array $p): array {
        return product_map_legacy($p);
    }, $products);
}

function products_count_by_category($db, $category_id): int
{
    try {
        $result = catalog_get_products_page((int) $category_id, 1, 1);

        return (int) ($result['meta']['total'] ?? 0);
    } catch (Throwable) {
        return 0;
    }
}

/**
 * @return array{items: list<array<string, mixed>>, meta: array{current_page: int, last_page: int, per_page: int, total: int}}
 */
function catalog_get_products_page(int $categoryId, int $page, int $perPage = 12): array
{
    $query = [
        'page' => max(1, $page),
        'per_page' => max(1, min(48, $perPage)),
    ];

    if ($categoryId > 0) {
        $query['category_id'] = $categoryId;
    }

    try {
        $result = api_client()->getProductsPaginated($query);
    } catch (Throwable) {
        return [
            'items' => [],
            'meta' => [
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => $perPage,
                'total' => 0,
            ],
        ];
    }

    return [
        'items' => array_map(static fn (array $p): array => product_map_legacy($p), $result['items']),
        'meta' => $result['meta'],
    ];
}
