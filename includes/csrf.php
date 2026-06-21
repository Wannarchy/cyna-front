<?php
/**
 * CYNA — Protection CSRF
 * 
 * Usage :
 *   1. require_once '../includes/csrf.php';
 *   2. Dans le formulaire HTML : <?= csrf_field() ?>
 *   3. Dans le traitement POST : csrf_verify();
 */

if (session_status() === PHP_SESSION_NONE) {
    if (function_exists('cyna_session_start')) {
        cyna_session_start();
    } else {
        session_start();
    }
}

/**
 * Générer ou récupérer le token CSRF de la session
 */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Retourner un champ hidden HTML avec le token
 */
function csrf_field() {
    return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

/**
 * Vérifier le token CSRF — stoppe l'exécution si invalide
 */
function csrf_verify(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        return;
    }

    $token_recu = $_POST['_csrf_token'] ?? '';
    $token_sess = $_SESSION['csrf_token'] ?? '';

    if (empty($token_recu) || empty($token_sess) || ! hash_equals($token_sess, $token_recu)) {
        csrf_fail();
    }

    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function csrf_fail(): void
{
    $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');
    $wantsJson = str_contains($accept, 'application/json')
        || strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';

    if ($wantsJson) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        die(json_encode(['error' => 'Token CSRF invalide. Rechargez la page et réessayez.']));
    }

    $_SESSION['flash_erreurs'] = ['Session expirée ou formulaire déjà envoyé. Réessayez.'];
    $target = basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'index.php'));

    header('Location: '.$target, true, 303);
    exit;
}

/**
 * Vérifier sans stopper — retourne true/false
 */
function csrf_check() {
    $token_recu = $_POST['_csrf_token'] ?? '';
    $token_sess = $_SESSION['csrf_token'] ?? '';
    return !empty($token_recu) && !empty($token_sess) && hash_equals($token_sess, $token_recu);
}