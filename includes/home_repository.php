<?php

require_once __DIR__ . '/api_client.php';
require_once __DIR__ . '/cloudinary.php';

if (! function_exists('product_is_available')) {
    function product_is_available(array $product): bool
    {
        if (array_key_exists('is_purchasable', $product)) {
            return ! empty($product['is_purchasable']);
        }

        if (array_key_exists('stock', $product)) {
            return ! empty($product['is_available']) && (int) $product['stock'] > 0;
        }

        return ! empty($product['is_available']);
    }
}

if (! function_exists('product_status_label')) {
    function product_status_label(array $product, string $lang = 'fr'): string
    {
        if (product_is_available($product)) {
            return match ($lang) {
                'en' => '● Available',
                'ar' => '● متاح',
                'he' => '● זמין',
                default => '● Disponible',
            };
        }

        $stockKnown = array_key_exists('stock', $product) && $product['stock'] !== null;
        $adminAvailable = array_key_exists('is_available_flag', $product)
            ? ! empty($product['is_available_flag'])
            : ! empty($product['is_available']);

        if ($stockKnown && (int) $product['stock'] <= 0 && $adminAvailable) {
            return match ($lang) {
                'en' => '● Out of stock',
                'ar' => '● نفد المخزون',
                'he' => '● אזל מהמלאי',
                default => '● Rupture de stock',
            };
        }

        return match ($lang) {
            'en' => '● Unavailable',
            'ar' => '● غير متاح',
            'he' => '● לא זמין',
            default => '● Indisponible',
        };
    }
}

function product_map_legacy(array $product): array
{
    $name = $product['name'] ?? ($product['nom_produit'] ?? '');
    $monthly = $product['price_monthly'] ?? ($product['prix_mensuel'] ?? 0);
    $yearly = $product['price_yearly'] ?? ($product['prix_annuel'] ?? 0);
    $image = $product['image_path'] ?? ($product['image'] ?? 'logo.jpg');
    $imageUrl = $product['image_url'] ?? null;
    $available = product_is_available($product) ? 1 : 0;
    $stock = array_key_exists('stock', $product) ? (int) $product['stock'] : null;

    return array_merge($product, [
        'id' => $product['id'] ?? 0,
        'category_id' => $product['category_id'] ?? null,
        'name' => $name,
        'nom_produit' => $name,
        'image_path' => $image,
        'image_url' => is_string($imageUrl) ? $imageUrl : cloudinary_delivery_url($image),
        'image' => $image,
        'price_monthly' => $monthly,
        'prix_mensuel' => $monthly,
        'price_yearly' => $yearly,
        'prix_annuel' => $yearly,
        'is_available_flag' => ! empty($product['is_available']),
        'is_available' => $available,
        'disponible' => $available,
        'stock' => $stock,
        'is_purchasable' => (bool) $available,
        'is_featured' => ! empty($product['is_featured']) ? 1 : 0,
        'category_name' => $product['category']['name'] ?? ($product['category_name'] ?? null),
        'nom_categorie' => $product['category']['name'] ?? ($product['category_name'] ?? null),
    ]);
}

function home_get_featured_products($db, int $limit = 8): array
{
    $products = [];

    try {
        $products = api_client()->getProducts(['is_featured' => 'true']);
    } catch (Throwable $e) {
        error_log('[CYNA API] home_get_featured_products (featured): '.$e->getMessage());
    }

    if ($products === []) {
        try {
            $all = api_client()->getProducts();
            $products = array_values(array_filter(
                $all,
                static fn (array $p): bool => ! empty($p['is_featured'])
            ));
            if ($products === [] && $all !== []) {
                $products = $all;
            }
        } catch (Throwable $e) {
            error_log('[CYNA API] home_get_featured_products (all): '.$e->getMessage());
            return [];
        }
    }

    $products = array_map('product_map_legacy', $products);

    if ($limit > 0) {
        $products = array_slice($products, 0, $limit);
    }

    return $products;
}

function home_get_slides($db): array
{
    try {
        $homepage = api_client()->getHomepage();
    } catch (Throwable $e) {
        error_log('[CYNA API] home_get_slides: '.$e->getMessage());
        return [];
    }

    return $homepage['slides'] ?? [];
}

function home_get_content($db): array
{
    try {
        $homepage = api_client()->getHomepage();
    } catch (Throwable $e) {
        error_log('[CYNA API] home_get_content: '.$e->getMessage());
        return [];
    }

    $content = $homepage['content'] ?? [];

    return is_array($content) ? $content : [];
}

function categories_get_all($db): array
{
    try {
        $categories = api_client()->getCategories();
    } catch (Throwable $e) {
        error_log('[CYNA API] categories_get_all: '.$e->getMessage());
        return [];
    }

    return array_map(static function (array $cat): array {
        $imagePath = $cat['image_path'] ?? 'logo.jpg';

        return [
            'id' => $cat['id'] ?? 0,
            'name' => $cat['name'] ?? '',
            'nom' => $cat['name'] ?? '',
            'image_path' => $imagePath,
            'image_url' => $cat['image_url'] ?? cloudinary_delivery_url(is_string($imagePath) ? $imagePath : null),
            'sort_order' => $cat['sort_order'] ?? 0,
            'is_active' => ! empty($cat['is_active']) ? 1 : 0,
        ];
    }, $categories);
}

function home_get_all_products($db): array
{
    try {
        $products = api_client()->getProducts();
    } catch (Throwable $e) {
        error_log('[CYNA API] home_get_all_products: '.$e->getMessage());
        return [];
    }

    return array_map('product_map_legacy', $products);
}

function cat_get_by_id($db, int $id): ?array
{
    foreach (categories_get_all($db) as $cat) {
        if ((int) ($cat['id'] ?? 0) === $id) {
            return $cat;
        }
    }

    return null;
}

function home_get_categories($db): array
{
    return categories_get_all($db);
}

function home_get_text($db, string $lang = 'fr'): string
{
    $content = home_get_content($db);
    if ($content === []) {
        return '';
    }

    $text = (string) ($content['content_text'] ?? '');
    if ($text === '') {
        return '';
    }

    $trimmed = trim($text);
    if (str_starts_with($trimmed, '{')) {
        $decoded = json_decode($trimmed, true);
        if (is_array($decoded)) {
            return (string) ($decoded[$lang] ?? $decoded['fr'] ?? (string) reset($decoded));
        }
    }

    return $text;
}

function slide_title(array $slide, string $lang): string
{
    $key = 'title_'.$lang;

    return (string) ($slide[$key] ?? $slide['title'] ?? '');
}

function slide_subtitle(array $slide, string $lang): string
{
    $key = 'subtitle_'.$lang;

    return (string) ($slide[$key] ?? $slide['subtitle'] ?? '');
}

function asset_image(?string $path, ?string $imageUrl = null): string
{
    return cloudinary_resolve_image_src($path, $imageUrl);
}

function image_display_src(?string $path, string $relativePrefix = '', ?string $imageUrl = null): string
{
    $resolved = asset_image($path, $imageUrl);

    if (preg_match('#^https?://#i', $resolved)) {
        return $resolved;
    }

    return $relativePrefix.$resolved;
}
