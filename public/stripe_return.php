<?php

require_once __DIR__ . '/../config/config.php';
cyna_session_start();

if (! isset($_SESSION['utilisateur_id'])) {
    header('Location: connexion.php');
    exit;
}

$order_id = (int) ($_SESSION['pending_order_id'] ?? 0);
unset($_SESSION['pending_order_id']);

if ($order_id > 0) {
    unset($_SESSION['cart']);
    header('Location: confirmation.php?order_id='.$order_id);
    exit;
}

header('Location: mes-commandes.php');
exit;
