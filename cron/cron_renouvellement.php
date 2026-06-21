<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(410);
    header('Content-Type: text/plain; charset=utf-8');
}

echo "Ce cron est désactivé. Les renouvellements sont gérés par le scheduler Laravel (php artisan schedule:run).\n";
exit;
