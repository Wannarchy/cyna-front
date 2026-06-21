<?php

$envFile = __DIR__.'/../.env';
if (is_readable($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        $value = trim($value, " \t\"'");
        putenv($key.'='.$value);
        $_ENV[$key] = $value;
    }
}

define('API_BASE_URL', rtrim(getenv('CYNA_API_URL') ?: 'https://laravel-api-1-zb19.onrender.com', '/'));
define('API_SSL_VERIFY', filter_var(getenv('CYNA_API_SSL_VERIFY') ?: 'false', FILTER_VALIDATE_BOOLEAN));
