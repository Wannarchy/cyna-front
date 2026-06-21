<?php

require_once __DIR__ . '/api_client.php';
require_once __DIR__ . '/home_repository.php';
require_once __DIR__ . '/admin_helpers.php';

function search_get_categories(): array
{
    try {
        return api_client()->getCategories();
    } catch (Throwable) {
        return [];
    }
}

function search_get_products(bool $includeUnavailable = false): array
{
    try {
        if ($includeUnavailable && ! empty($_SESSION['is_admin']) && ! empty($_SESSION['api_token'])) {
            $products = admin_api()->adminGetProducts();
        } else {
            $products = api_client()->getProducts();
        }
    } catch (Throwable) {
        return [];
    }

    return array_map('product_map_legacy', $products);
}

function search_filter_products(array $products, array $filters): array
{
    $q = trim($filters['q'] ?? '');
    $catId = (int) ($filters['cat_id'] ?? 0);
    $priceMin = isset($filters['price_min']) && $filters['price_min'] !== '' ? (float) $filters['price_min'] : null;
    $priceMax = isset($filters['price_max']) && $filters['price_max'] !== '' ? (float) $filters['price_max'] : null;
    $dispoOnly = ! empty($filters['dispo_only']);
    $sort = $filters['sort'] ?? 'pertinence';

    $results = array_values(array_filter($products, static function (array $product) use ($q, $catId, $priceMin, $priceMax, $dispoOnly): bool {
        if ($catId > 0 && (int) ($product['category_id'] ?? 0) !== $catId) {
            return false;
        }
        if ($dispoOnly && ! product_is_available($product)) {
            return false;
        }
        if ($priceMin !== null && (float) ($product['price_monthly'] ?? 0) < $priceMin) {
            return false;
        }
        if ($priceMax !== null && (float) ($product['price_monthly'] ?? 0) > $priceMax) {
            return false;
        }
        if ($q !== '' && stripos($product['name'] ?? '', $q) === false) {
            return false;
        }

        return true;
    }));

    foreach ($results as &$product) {
        $name = $product['name'] ?? '';
        $relevance = 25;
        if ($q !== '') {
            if (strcasecmp($name, $q) === 0) {
                $relevance = 100;
            } elseif (stripos($name, $q) === 0) {
                $relevance = 75;
            } elseif (stripos($name, $q) !== false) {
                $relevance = 50;
            }
        }
        $product['relevance'] = $relevance;
        $category = $product['category'] ?? null;
        $product['cat_name'] = is_array($category) ? ($category['name'] ?? null) : null;
    }
    unset($product);

    usort($results, static function (array $a, array $b) use ($sort): int {
        return match ($sort) {
            'price_asc' => (float) $a['price_monthly'] <=> (float) $b['price_monthly'],
            'price_desc' => (float) $b['price_monthly'] <=> (float) $a['price_monthly'],
            'newest' => (int) ($b['id'] ?? 0) <=> (int) ($a['id'] ?? 0),
            'dispo' => (int) product_is_available($b) <=> (int) product_is_available($a)
                ?: ((float) $a['price_monthly'] <=> (float) $b['price_monthly']),
            default => ((int) ($b['relevance'] ?? 0)) <=> ((int) ($a['relevance'] ?? 0))
                ?: (int) product_is_available($b) <=> (int) product_is_available($a)
                ?: ((int) ! empty($b['is_featured'])) <=> ((int) ! empty($a['is_featured']))
                ?: ((float) $a['price_monthly'] <=> (float) $b['price_monthly']),
        };
    });

    return $results;
}

function search_price_bounds(array $products): array
{
    $available = array_filter($products, static fn (array $product): bool => product_is_available($product));
    if (! $available) {
        return [0.0, 9999.0];
    }

    $prices = array_map(static fn (array $product): float => (float) ($product['price_monthly'] ?? 0), $available);

    return [min($prices), max($prices)];
}
