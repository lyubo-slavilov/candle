<?php 

use Candle\Url\Router;
$router = Router::getInstance();

$router->rule('^/?$', 'home', array(
    'controller' => 'main', 
    'action' => 'default',
));


$router->rule('/tester/:action', 'tester', array(
    'controller' => 'tester'
));

//remove before production!
$router->rule('/:controller/:action/?', 'default');