<?php

use Candle\Http\Response;

use Candle\Config;

use Candle\Url\Router;

use Candle\Exception\Error404Exception;
use Candle\Exception\HttpRedirectException;

use Candle\Http\Request;

if (CANDLE_ENVIRONMENT == 'prod') {
    error_reporting(0);
    ini_set('display_errors', 0);
}

define('CANDLE_INSTALL_DIR', str_replace(DIRECTORY_SEPARATOR . 'framework', '', __DIR__));
define('CANDLE_APP_BASE_DIR', __DIR__ . '/../app');
define('CANDLE_APP_DIR', CANDLE_APP_BASE_DIR . '/' . strtolower(CANDLE_APP_NAME));

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
    $content = ob_get_contents();
    
    ob_end_clean();
    
    Response::getInstance()->setContent($content);
    Response::getInstance()->send();
}



//Deal with catchable PHP errors
set_error_handler(function ($errno , $errstr, $errfile = null, $errline = null, array $errcontext = array()) {
    //forward all errors to error500 page
    
    $ex = new Exception("{$errno}: {$errstr} at {$errfile} {$errline}");
    Request::getInstance()->setParam('exception', $ex);
    
    candleExec(Config::get('wick.error500'));
    
    return true;
    
}, E_ALL);


//Register the autoloaders
require (__DIR__ . '/autoload.php');


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
    
    ob_end_clean();
    if ($ex instanceof HttpRedirectException) {
        header('Location: ' . $ex->getUrl());
        die('Location: ' . $ex->getUrl());
    }
    if ($ex instanceof Error404Exception) {
        $errorAction = Config::get('wick.error404');
    } else {
        $errorAction = Config::get('wick.error500');
    }
    $request->setParam('exception', $ex);
    
    candleExec($errorAction);
    
}
    
