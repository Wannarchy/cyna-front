<?php
require_once __DIR__ . '/header.php';

$userId = (int) ($_POST['user_id'] ?? 0);
$bloquer = ($_POST['bloquer'] ?? '') === '1';
$returnUrl = trim((string) ($_POST['return_url'] ?? 'audit_logs.php'));
if ($returnUrl === '' || ! str_starts_with($returnUrl, 'audit_logs.php')) {
    $returnUrl = 'audit_logs.php';
}

if ($userId <= 0) {
    header('Location: '.$returnUrl.(str_contains($returnUrl, '?') ? '&' : '?').'error='.urlencode('Utilisateur invalide.'));
    exit;
}

try {
    admin_api()->adminSetUserBlocked($userId, $bloquer);
    $message = $bloquer ? 'Utilisateur bloqué.' : 'Utilisateur débloqué.';
    header('Location: '.$returnUrl.(str_contains($returnUrl, '?') ? '&' : '?').'success='.urlencode($message));
    exit;
} catch (RuntimeException $e) {
    header('Location: '.$returnUrl.(str_contains($returnUrl, '?') ? '&' : '?').'error='.urlencode($e->getMessage()));
    exit;
}
