<?php

require_once __DIR__ . '/../config/config.php';
cyna_session_start();

require_once __DIR__ . '/../includes/admin_helpers.php';

admin_require_auth();
