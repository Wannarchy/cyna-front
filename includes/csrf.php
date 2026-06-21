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
function csrf_verify() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

    $token_recu = $_POST['_csrf_token'] ?? '';
    $token_sess = $_SESSION['csrf_token'] ?? '';

    if (empty($token_recu) || empty($token_sess) || !hash_equals($token_sess, $token_recu)) {
        http_response_code(403);
        die(json_encode(['error' => 'Token CSRF invalide. Rechargez la page et réessayez.']));
    }

    // Régénérer le token après validation (rotation)
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/**
 * Vérifier sans stopper — retourne true/false
 */
function csrf_check() {
    $token_recu = $_POST['_csrf_token'] ?? '';
    $token_sess = $_SESSION['csrf_token'] ?? '';
    return !empty($token_recu) && !empty($token_sess) && hash_equals($token_sess, $token_recu);
}