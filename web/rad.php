<?php

$ip = $_SERVER['REMOTE_ADDR'];

if ($ip != '127.0.0.1') {
    header('HTTP/1.1 403: FORBIDDEN', true, 403);
    die('');
}

define('CANDLE_WEB_DIR', __DIR__ );
define('CANDLE_APP_NAME', 'rad');
define('CANDLE_ENVIRONMENT', 'dev');


require_once __DIR__.'/../framework/candle.php';