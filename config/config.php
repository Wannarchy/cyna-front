<?php

require_once __DIR__ . '/api.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/api_client.php';
require_once __DIR__ . '/../includes/activity_log.php';

$connexion = null;

if (PHP_SAPI !== 'cli' && ! defined('CYNA_SKIP_PAGE_LOG')) {
    if (session_status() === PHP_SESSION_NONE) {
        if (function_exists('cyna_session_start')) {
            cyna_session_start();
        } else {
            session_start();
        }
    }

    $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
    $isAdminArea = str_contains(str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME'] ?? ''), '/admin/');

    if ($script !== '' && ! $isAdminArea) {
        cyna_audit_maybe_log_page($script);
    }
}
