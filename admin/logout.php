<?php

require_once __DIR__ . '/../config/config.php';
cyna_session_start();

if (! empty($_SESSION['api_token'])) {
    try {
        api_client()->logout();
    } catch (Throwable) {
    }
}

cyna_session_destroy();

header('Location: login.php');
exit;
