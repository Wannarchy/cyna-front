<?php

require_once __DIR__ . '/../config/config.php';
cyna_session_start();

try {
    api_client()->logout();
} catch (Throwable) {
}

cyna_session_destroy();

header('Location: ../index.php');
exit;
