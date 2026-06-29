<?php

require_once __DIR__ . '/api_client.php';
require_once __DIR__ . '/cloudinary.php';

function admin_api(): ApiClient
{
    return api_client();
}

function admin_require_auth(): void
{
    if (empty($_SESSION['api_token']) || empty($_SESSION['is_admin'])) {
        header('Location: login.php');
        exit;
    }
}

function admin_product_row(array $product): array
{
    $category = $product['category'] ?? null;

    return [
        'id' => (int) ($product['id'] ?? 0),
        'category_id' => $product['category_id'] ?? null,
        'name' => $product['name'] ?? '',
        'image_path' => $product['image_path'] ?? '',
        'price_monthly' => (float) ($product['price_monthly'] ?? 0),
        'price_yearly' => (float) ($product['price_yearly'] ?? 0),
        'is_available' => ! empty($product['is_available']) ? 1 : 0,
        'stock' => (int) ($product['stock'] ?? 0),
        'is_featured' => ! empty($product['is_featured']) ? 1 : 0,
        'featured_order' => (int) ($product['featured_order'] ?? 999),
        'cat_name' => is_array($category) ? ($category['name'] ?? null) : null,
        'description' => trim((string) ($product['description'] ?? '')),
        'technical_specs' => admin_parse_technical_specs($product['technical_specs'] ?? []),
    ];
}

function admin_order_row(array $order): array
{
    $user = $order['user'] ?? [];

    return [
        'id' => (int) ($order['id'] ?? 0),
        'user_id' => (int) ($order['user_id'] ?? 0),
        'total' => (float) ($order['total'] ?? 0),
        'billing_name' => $order['billing_name'] ?? '',
        'billing_address' => $order['billing_address'] ?? '',
        'status' => $order['status'] ?? null,
        'created_at' => $order['created_at'] ?? null,
        'email' => is_array($user) ? ($user['email'] ?? null) : null,
        'prenom' => is_array($user) ? ($user['prenom'] ?? null) : null,
        'nom' => is_array($user) ? ($user['nom'] ?? null) : null,
        'items' => $order['items'] ?? [],
    ];
}

function admin_user_row(array $user, array $orders = []): array
{
    $userOrders = array_filter($orders, static function (array $order) use ($user): bool {
        return (int) ($order['user_id'] ?? 0) === (int) ($user['id'] ?? 0);
    });

    $totalSpent = array_sum(array_map(static function (array $order): float {
        return (float) ($order['total'] ?? 0);
    }, $userOrders));

    return [
        'id' => (int) ($user['id'] ?? 0),
        'prenom' => $user['prenom'] ?? '',
        'nom' => $user['nom'] ?? '',
        'email' => $user['email'] ?? '',
        'est_confirme' => ! empty($user['est_confirme']) ? 1 : 0,
        'is_admin' => ! empty($user['is_admin']) ? 1 : 0,
        'est_actif' => ! isset($user['est_actif']) || ! empty($user['est_actif']) ? 1 : 0,
        'bloquer' => ! empty($user['bloquer']) ? 1 : 0,
        'nb_orders' => count($userOrders),
        'total_spent' => $totalSpent,
    ];
}

function admin_bool($value): bool
{
    return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (bool) $value;
}

function admin_product_name_taken(string $name, ?int $exceptId = null): bool
{
    $normalized = mb_strtolower(trim($name));

    if ($normalized === '') {
        return false;
    }

    try {
        foreach (admin_api()->adminGetProducts() as $product) {
            $productId = (int) ($product['id'] ?? 0);
            if ($exceptId !== null && $productId === $exceptId) {
                continue;
            }

            if (mb_strtolower(trim((string) ($product['name'] ?? ''))) === $normalized) {
                return true;
            }
        }
    } catch (RuntimeException) {
        return false;
    }

    return false;
}

function admin_product_category_id(array $input): int
{
    return (int) ($input['category_id'] ?? 0);
}

/**
 * @return list<string>
 */
function admin_parse_technical_specs(mixed $input): array
{
    if (is_array($input)) {
        return array_values(array_filter(array_map(
            static fn ($line): string => trim((string) $line),
            $input
        )));
    }

    $raw = trim((string) $input);
    if ($raw === '') {
        return [];
    }

    $lines = preg_split('/\R/', $raw) ?: [];

    return array_values(array_filter(array_map('trim', $lines)));
}

/**
 * @param array<string, mixed>|null $file
 */
function admin_request_has_image_file(?array $file): bool
{
    if ($file === null) {
        return false;
    }

    $name = trim((string) ($file['name'] ?? ''));

    return $name !== '' && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
}

function admin_upload_error_message(int $error): string
{
    return match ($error) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Image trop volumineuse (limite PHP dépassée).',
        UPLOAD_ERR_PARTIAL => 'Upload interrompu, réessayez.',
        UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant sur le serveur.',
        UPLOAD_ERR_CANT_WRITE => 'Impossible d\'écrire l\'image sur le disque.',
        UPLOAD_ERR_EXTENSION => 'Upload bloqué par une extension PHP.',
        default => 'Erreur upload (code '.$error.').',
    };
}

/**
 * Upload une image produit vers Cloudinary si un fichier a été sélectionné.
 * Retourne null si aucun fichier, l'URL Cloudinary si OK.
 *
 * @param array<string, mixed>|null $file
 */
function admin_upload_product_image(?array $file, string $folder = 'products'): ?string
{
    if (! admin_request_has_image_file($file)) {
        return null;
    }

    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error !== UPLOAD_ERR_OK) {
        throw new RuntimeException(admin_upload_error_message($error));
    }

    if (empty($file['tmp_name']) || ! is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('Fichier image invalide ou non reçu par le serveur.');
    }

    $upload = admin_api()->adminUploadImage($file, $folder);
    $publicId = trim((string) ($upload['public_id'] ?? ''));

    if ($publicId === '') {
        $fallbackUrl = trim((string) ($upload['url'] ?? $upload['secure_url'] ?? ''));
        if ($fallbackUrl !== '') {
            $publicId = cloudinary_normalize_for_storage($fallbackUrl) ?? '';
        }
    }

    if ($publicId === '') {
        throw new RuntimeException('Upload terminé sans identifiant Cloudinary.');
    }

    return $publicId;
}

function admin_product_payload(array $input, ?string $newImagePath = null, ?string $currentImagePath = null): array
{
    $categoryId = admin_product_category_id($input);

    if ($categoryId <= 0) {
        throw new InvalidArgumentException('La catégorie est obligatoire.');
    }

    $payload = [
        'name' => trim($input['name'] ?? ''),
        'category_id' => $categoryId,
        'description' => trim($input['description'] ?? ''),
        'technical_specs' => admin_parse_technical_specs($input['technical_specs'] ?? ''),
        'price_monthly' => (float) ($input['price_monthly'] ?? 0),
        'price_yearly' => (float) ($input['price_yearly'] ?? 0),
        'is_available' => admin_bool($input['is_available'] ?? true),
        'stock' => max(0, (int) ($input['stock'] ?? 0)),
        'requires_shipping' => admin_bool($input['requires_shipping'] ?? false),
        'is_featured' => admin_bool($input['is_featured'] ?? false),
        'featured_order' => (int) ($input['featured_order'] ?? 999),
    ];

    if ($newImagePath !== null && trim($newImagePath) !== '') {
        $payload['image_path'] = trim($newImagePath);
    } elseif ($currentImagePath !== null && trim($currentImagePath) !== '' && ! in_array(trim($currentImagePath), ['logo.jpg', 'logo.png'], true)) {
        // Mise à jour sans nouvelle image : conserver l'URL Cloudinary existante.
        $payload['image_path'] = trim($currentImagePath);
    }

    return $payload;
}

function admin_category_payload(array $input, ?string $newImagePath = null, ?string $currentImagePath = null): array
{
    $sortOrder = max(1, (int) ($input['sort_order'] ?? 1));

    $payload = [
        'name' => trim($input['name'] ?? ''),
        'sort_order' => $sortOrder,
        'is_active' => admin_bool($input['is_active'] ?? true),
    ];

    if ($newImagePath !== null && trim($newImagePath) !== '') {
        $payload['image_path'] = trim($newImagePath);
    } elseif ($currentImagePath !== null && trim($currentImagePath) !== '') {
        $payload['image_path'] = trim($currentImagePath);
    } elseif (trim($input['image_path'] ?? '') !== '') {
        $payload['image_path'] = trim($input['image_path']);
    }

    return $payload;
}

function admin_category_sort_order_taken(int $sortOrder, ?int $exceptId = null): bool
{
    if ($sortOrder < 1) {
        return false;
    }

    try {
        foreach (admin_api()->adminGetCategories() as $category) {
            $categoryId = (int) ($category['id'] ?? 0);
            if ($exceptId !== null && $categoryId === $exceptId) {
                continue;
            }

            if ((int) ($category['sort_order'] ?? 0) === $sortOrder) {
                return true;
            }
        }
    } catch (RuntimeException) {
        return false;
    }

    return false;
}

function admin_category_next_sort_order(): int
{
    try {
        $orders = array_map(
            static fn (array $category): int => (int) ($category['sort_order'] ?? 0),
            admin_api()->adminGetCategories()
        );
    } catch (RuntimeException) {
        return 1;
    }

    if ($orders === []) {
        return 1;
    }

    return max($orders) + 1;
}

function admin_slide_payload(array $input, ?string $newImagePath = null, ?string $currentImagePath = null): array
{
    $payload = [
        'title' => trim($input['title'] ?? ''),
        'subtitle' => trim($input['subtitle'] ?? '') ?: null,
        'link_url' => trim($input['link_url'] ?? '') ?: null,
        'sort_order' => (int) ($input['sort_order'] ?? 1),
        'is_active' => admin_bool($input['is_active'] ?? true),
    ];

    if ($newImagePath !== null && trim($newImagePath) !== '') {
        $payload['image_path'] = trim($newImagePath);
    } elseif ($currentImagePath !== null && trim($currentImagePath) !== '' && ! in_array(trim($currentImagePath), ['logo.jpg', 'logo.png'], true)) {
        $payload['image_path'] = trim($currentImagePath);
    } else {
        $manualPath = trim($input['image_path'] ?? '');
        $payload['image_path'] = $manualPath !== '' ? $manualPath : 'logo.jpg';
    }

    if ((int) ($input['id'] ?? 0) > 0) {
        $payload['id'] = (int) $input['id'];
    }

    return $payload;
}

/**
 * Liste les slides admin (toutes, y compris inactives).
 * Repli sur GET /homepage si l'API déployée n'a pas encore GET /admin/homepage/slides.
 */
function admin_list_slides(): array
{
    try {
        return admin_api()->adminGetSlides();
    } catch (RuntimeException $e) {
        $message = $e->getMessage();
        if (stripos($message, 'GET method is not supported') !== false
            || stripos($message, 'Method Not Allowed') !== false) {
            $homepage = admin_api()->getHomepage();

            return $homepage['slides'] ?? [];
        }

        throw $e;
    }
}

function admin_log_row(array $log): array
{
    $user = is_array($log['user'] ?? null) ? $log['user'] : [];
    if ($user === [] && is_array($log['admin'] ?? null)) {
        $user = $log['admin'];
    }

    $userId = isset($log['user_id']) ? (int) $log['user_id'] : null;
    if ($userId <= 0 && isset($log['admin_id'])) {
        $userId = (int) $log['admin_id'];
    }

    $actorType = (string) ($log['actor_type'] ?? '');
    if ($actorType === '') {
        $actorType = $userId > 0 ? 'user' : 'guest';
    }

    $actorName = trim(($user['prenom'] ?? '').' '.($user['nom'] ?? ''));
    $actorEmail = (string) ($user['email'] ?? '');

    return [
        'id' => (int) ($log['id'] ?? 0),
        'actor_type' => $actorType,
        'user_id' => $userId > 0 ? $userId : null,
        'actor_name' => $actorName,
        'actor_email' => $actorEmail,
        'action' => (string) ($log['action'] ?? ''),
        'target_type' => $log['target_type'] ?? null,
        'target_id' => isset($log['target_id']) ? (int) $log['target_id'] : null,
        'ip' => $log['ip'] ?? '',
        'details' => is_string($log['details'] ?? null)
            ? $log['details']
            : (is_array($log['details'] ?? null) ? ($log['details']['method'] ?? json_encode($log['details'])) : ''),
        'created_at' => $log['created_at'] ?? null,
    ];
}

function admin_audit_log_row(array $log): array
{
    return admin_log_row($log);
}

function audit_log_blockable_user_id(array $log): ?int
{
    if (($log['actor_type'] ?? '') === 'user' && ! empty($log['user_id'])) {
        return (int) $log['user_id'];
    }

    if (($log['target_type'] ?? '') === 'User' && ! empty($log['target_id'])) {
        return (int) $log['target_id'];
    }

    return null;
}

/**
 * @param  callable(array<string, int|string>): string  $buildUrl
 */
function admin_render_pagination(int $page, int $totalPages, int $total, string $itemLabel, callable $buildUrl, bool $insideCard = false): void
{
    if ($totalPages <= 1) {
        return;
    }

    $wrapStart = $insideCard
        ? '<div style="padding:16px 20px;border-top:1px solid var(--c-border)">'
        : '<div class="card" style="margin-top:16px"><div style="padding:16px 20px">';
    $wrapEnd = $insideCard ? '</div>' : '</div></div>';

    echo $wrapStart;
    echo '<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">';
    echo '<div style="font-size:.78rem;color:var(--c-muted)">Page '.$page.' / '.$totalPages.' — '.$total.' '.htmlspecialchars($itemLabel).'</div>';
    echo '<div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap">';
    if ($page > 1) {
        echo '<a href="'.htmlspecialchars($buildUrl(['page' => $page - 1])).'" class="btn-view">← Préc</a>';
    }
    for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++) {
        $active = $i === $page;
        echo '<a href="'.htmlspecialchars($buildUrl(['page' => $i])).'" style="display:inline-flex;align-items:center;justify-content:center;min-width:34px;height:34px;border-radius:8px;text-decoration:none;font-size:.78rem;font-weight:700;';
        echo $active
            ? 'background:var(--grad);color:#fff'
            : 'background:rgba(255,255,255,.06);border:1px solid var(--c-border2);color:var(--c-muted2)';
        echo '">'.$i.'</a>';
    }
    if ($page < $totalPages) {
        echo '<a href="'.htmlspecialchars($buildUrl(['page' => $page + 1])).'" class="btn-view">Suiv →</a>';
    }
    echo '</div></div>'.$wrapEnd;
}
