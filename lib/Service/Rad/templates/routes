<?php 

use Candle\Url\Router;
$router = Router::getInstance();

$router->rule('/?', 'home', array(
    'controller' => 'main', 
    'action' => 'home',
));

//remove before production!
$router->rule('/:controller/:action/?', 'default');