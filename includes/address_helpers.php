<?php

/**
 * @param array<string, mixed> $address
 */
function address_billing_name(array $address): string
{
    return trim(trim($address['prenom'] ?? '').' '.trim($address['nom'] ?? ''));
}

/**
 * @param array<string, mixed> $address
 */
function address_format_multiline(array $address): string
{
    $lines = [];

    if (trim($address['adresse1'] ?? '') !== '') {
        $lines[] = trim($address['adresse1']);
    }

    if (trim($address['adresse2'] ?? '') !== '') {
        $lines[] = trim($address['adresse2']);
    }

    $cityLine = trim(trim($address['code_postal'] ?? '').' '.trim($address['ville'] ?? ''));
    if ($cityLine !== '') {
        $lines[] = $cityLine;
    }

    if (trim($address['region'] ?? '') !== '') {
        $lines[] = trim($address['region']);
    }

    if (trim($address['pays'] ?? '') !== '') {
        $lines[] = trim($address['pays']);
    }

    if (trim($address['telephone'] ?? '') !== '') {
        $lines[] = 'Tél. '.trim($address['telephone']);
    }

    return implode("\n", $lines);
}

/**
 * @param array<string, mixed> $input
 * @return array<string, string>
 */
function address_from_input(array $input, string $prefix = ''): array
{
    $p = $prefix !== '' ? $prefix : '';

    return [
        'label' => trim($input[$p.'label'] ?? $input['label'] ?? 'Adresse'),
        'usage_type' => trim($input[$p.'usage_type'] ?? $input['usage_type'] ?? 'both'),
        'prenom' => trim($input[$p.'prenom'] ?? $input['prenom'] ?? ''),
        'nom' => trim($input[$p.'nom'] ?? $input['nom'] ?? ''),
        'adresse1' => trim($input[$p.'adresse1'] ?? $input['adresse1'] ?? ''),
        'adresse2' => trim($input[$p.'adresse2'] ?? $input['adresse2'] ?? ''),
        'ville' => trim($input[$p.'ville'] ?? $input['ville'] ?? ''),
        'region' => trim($input[$p.'region'] ?? $input['region'] ?? ''),
        'code_postal' => trim($input[$p.'code_postal'] ?? $input['code_postal'] ?? ''),
        'pays' => trim($input[$p.'pays'] ?? $input['pays'] ?? 'France'),
        'telephone' => trim($input[$p.'telephone'] ?? $input['telephone'] ?? ''),
    ];
}

/**
 * @param array<string, mixed> $input
 * @return string[]
 */
function address_validate_required(array $input): array
{
    $errors = [];
    $fields = [
        'prenom' => 'Le prénom est requis.',
        'nom' => 'Le nom est requis.',
        'adresse1' => "L'adresse est requise.",
        'ville' => 'La ville est requise.',
        'code_postal' => 'Le code postal est requis.',
        'pays' => 'Le pays est requis.',
    ];

    foreach ($fields as $key => $message) {
        if (trim($input[$key] ?? '') === '') {
            $errors[] = $message;
        }
    }

    return $errors;
}

function address_country_options(): array
{
    return ['France', 'Belgique', 'Suisse', 'Luxembourg', 'Canada', 'États-Unis', 'Maroc', 'Algérie', 'Tunisie', 'Autre'];
}

/**
 * @return array<string, string>
 */
function address_usage_type_options(string $lang = 'fr'): array
{
    return match ($lang) {
        'en' => [
            'billing' => 'Billing only',
            'shipping' => 'Delivery only',
            'both' => 'Billing & delivery',
        ],
        'ar' => [
            'billing' => 'فوترة فقط',
            'shipping' => 'توصيل فقط',
            'both' => 'فوترة وتوصيل',
        ],
        'he' => [
            'billing' => 'חיוב בלבד',
            'shipping' => 'משלוח בלבד',
            'both' => 'חיוב ומשלוח',
        ],
        default => [
            'billing' => 'Facturation uniquement',
            'shipping' => 'Livraison uniquement',
            'both' => 'Facturation et livraison',
        ],
    };
}

function address_usage_label(string $usageType, string $lang = 'fr'): string
{
    $options = address_usage_type_options($lang);

    return $options[$usageType] ?? $usageType;
}

/**
 * @param list<array<string, mixed>> $addresses
 * @return list<array<string, mixed>>
 */
function address_filter_for_billing(array $addresses): array
{
    return array_values(array_filter(
        $addresses,
        static fn (array $address): bool => in_array($address['usage_type'] ?? 'both', ['billing', 'both'], true)
    ));
}

/**
 * @param list<array<string, mixed>> $addresses
 * @return list<array<string, mixed>>
 */
function address_filter_for_shipping(array $addresses): array
{
    return array_values(array_filter(
        $addresses,
        static fn (array $address): bool => in_array($address['usage_type'] ?? 'both', ['shipping', 'both'], true)
    ));
}

/**
 * @param list<array<string, mixed>> $items
 */
function cart_requires_shipping(array $items): bool
{
    foreach ($items as $item) {
        if (! empty($item['requires_shipping'])) {
            return true;
        }
    }

    return false;
}

/**
 * @param list<array<string, mixed>> $addresses
 */
function address_default_billing(?array $addresses): ?array
{
    if (! $addresses) {
        return null;
    }

    foreach ($addresses as $address) {
        if (! empty($address['is_default'])) {
            return $address;
        }
    }

    return $addresses[0] ?? null;
}

/**
 * @param list<array<string, mixed>> $addresses
 */
function address_default_shipping(?array $addresses): ?array
{
    if (! $addresses) {
        return null;
    }

    foreach ($addresses as $address) {
        if (! empty($address['is_default_shipping'])) {
            return $address;
        }
    }

    return $addresses[0] ?? null;
}
