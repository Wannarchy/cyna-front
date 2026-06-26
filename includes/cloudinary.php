<?php

function cloudinary_cloud_name(): string
{
    $name = getenv('CLOUDINARY_CLOUD_NAME') ?: ($_ENV['CLOUDINARY_CLOUD_NAME'] ?? '');

    if (trim((string) $name) === '' && defined('CLOUDINARY_CLOUD_NAME')) {
        $name = CLOUDINARY_CLOUD_NAME;
    }

    return trim((string) $name);
}

function cloudinary_is_delivery_url(?string $value): bool
{
    return is_string($value)
        && preg_match('#^https?://res\.cloudinary\.com/#i', $value) === 1;
}

function cloudinary_is_local_asset(?string $value): bool
{
    if ($value === null || trim($value) === '') {
        return true;
    }

    $value = trim(str_replace('\\', '/', $value));

    if (in_array($value, ['logo.jpg', 'logo.png'], true)) {
        return true;
    }

    if (str_starts_with($value, 'assets/') || str_starts_with($value, '/assets/')) {
        return true;
    }

    return ! str_contains($value, '/') && ! cloudinary_is_delivery_url($value);
}

function cloudinary_extract_public_id_from_url(string $url): ?string
{
    if (! cloudinary_is_delivery_url($url)) {
        return null;
    }

    $path = parse_url($url, PHP_URL_PATH);
    if (! is_string($path) || ! preg_match('#/image/upload/(.*)$#', $path, $matches)) {
        return null;
    }

    $segments = explode('/', $matches[1]);
    $start = 0;

    foreach ($segments as $index => $segment) {
        if (preg_match('/^v\d+$/', $segment)) {
            $start = $index + 1;
            break;
        }
    }

    if ($start === 0) {
        foreach ($segments as $index => $segment) {
            if ($segment === 'cyna') {
                $start = $index;
                break;
            }
        }
    }

    $publicIdWithExtension = implode('/', array_slice($segments, $start));
    if ($publicIdWithExtension === '') {
        return null;
    }

    return preg_replace('/\.[^.\/]+$/', '', $publicIdWithExtension) ?: $publicIdWithExtension;
}

function cloudinary_normalize_for_storage(?string $value): ?string
{
    if ($value === null) {
        return null;
    }

    $value = trim($value);
    if ($value === '') {
        return null;
    }

    if (cloudinary_is_delivery_url($value)) {
        return cloudinary_extract_public_id_from_url($value) ?? $value;
    }

    if (cloudinary_is_local_asset($value)) {
        return $value;
    }

    return ltrim(str_replace('\\', '/', $value), '/');
}

function cloudinary_delivery_url(?string $stored): ?string
{
    if ($stored === null || trim($stored) === '') {
        return null;
    }

    $stored = trim($stored);

    if (cloudinary_is_delivery_url($stored)) {
        return $stored;
    }

    if (cloudinary_is_local_asset($stored)) {
        return $stored;
    }

    if (preg_match('#^https?://#i', $stored)) {
        return $stored;
    }

    $cloudName = cloudinary_cloud_name();
    if ($cloudName === '') {
        return $stored;
    }

    return 'https://res.cloudinary.com/'.$cloudName.'/image/upload/'.ltrim($stored, '/');
}

function cloudinary_resolve_image_src(?string $path, ?string $imageUrl = null): string
{
    $imageUrl = trim((string) ($imageUrl ?? ''));
    if ($imageUrl !== '' && preg_match('#^https?://#i', $imageUrl)) {
        return $imageUrl;
    }

    $path = trim((string) ($path ?? ''));
    if ($path === '' || in_array($path, ['logo.jpg', 'logo.png'], true)) {
        return 'assets/images/logo.jpg';
    }

    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }

    $resolved = cloudinary_delivery_url($path);
    if ($resolved !== null && preg_match('#^https?://#i', $resolved)) {
        return $resolved;
    }

    return ltrim(str_replace('\\', '/', $path), '/');
}
