<?php


use Candle\Exception\InvalidControllerOrActionException;

use Candle\Http\Response;

use Candle\Config;

use Candle\Url\Router;

use Candle\Exception\Error404Exception;
use Candle\Exception\HttpRedirectException;
use Candle\Exception\BootstrapException;

use Candle\Http\Request;

if (CANDLE_ENVIRONMENT == 'prod') {
    error_reporting(0);
    ini_set('display_errors', 0);
} else if (CANDLE_ENVIRONMENT == 'dev') {
    error_reporting(E_ALL & ~E_NOTICE);
    ini_set('display_errors', 1);
}

//Register the autoloaders
require (__DIR__ . '/autoload.php');

define('CANDLE_INSTALL_DIR', str_replace(DIRECTORY_SEPARATOR . 'framework', '', __DIR__));
define('CANDLE_APP_BASE_DIR', __DIR__ . '/../app');
define('CANDLE_APP_DIR', CANDLE_APP_BASE_DIR . '/' . strtolower(CANDLE_APP_NAME));

$globalsFile = CANDLE_APP_DIR . DIRECTORY_SEPARATOR . 'globals.php';

if (file_exists($globalsFile)) {
    require_once $globalsFile;
}

/**
 * Main execution procedure
 * @param string $controller
 * @param string $action
 */
function candleExec($controller, $action = null)
{
    $bootstrap = new Candle\Bootstrap();

    ob_start();

    $bootstrap->run($controller, $action);
    $content = ob_get_clean();

    //ob_end_clean();
    $resp = Response::getInstance();
    Response::getInstance()->setContent($content);
    Response::getInstance()->send();
}



//Deal with catchable PHP errors
if (CANDLE_ENVIRONMENT == 'dev') {
    set_error_handler(function ($errno , $errstr, $errfile = null, $errline = null, array $errcontext = array() ) {

        throw new BootstrapException("{$errno}: {$errstr} at {$errfile} {$errline}");

    }, E_ALL & ~E_NOTICE);
}

//Try to run the framework
try {

    $request = Request::getInstance();

    include CANDLE_APP_DIR . '/routes.php';

    if (Router::getInstance()->resolve($request)) {
        candleExec($request->getParam('controller'), $request->getParam('action'));
    } else {
        throw new Error404Exception("Route not found for '{$request->getParam('route')}'");
    }

} catch (\Exception $ex) {

    //Deal with exceptions

    if (ob_get_level()) {
        ob_end_clean();
    }
    if ($ex instanceof HttpRedirectException) {
        header('Location: ' . $ex->getUrl());
        die();
    }
    if ($ex instanceof Error404Exception || $ex instanceof InvalidControllerOrActionException) {
        $errorAction = Config::get('wick.error404');
        $fallbackHttpStatus = '404: Not Found';
        $fallbackCode = 404;
    } else {
        $errorAction = Config::get('wick.error500');
        $fallbackHttpStatus = '500: Internal Server Error';
        $fallbackCode = 500;
    }
    $request->setParam('exception', $ex);

    try {
        candleExec($errorAction);
    } catch (\Exception $ex) {
        //It must be
        //InvalidControllerOrActionException
        //or BootstrapException
        //nothing can be done
        header("HTTP/1.1 {$fallbackHttpStatus}", true, $fallbackCode);
        echo "<h1>HTTP {$fallbackHttpStatus}</h1>";
    }
}

