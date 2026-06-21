<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/function.php';

startSession();
api_client()->logout();
redirectTo('../public/connexion.php');
exit();
