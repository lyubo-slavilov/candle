<?php 

use Candle\Url\Router;
$router = Router::getInstance();

$router->rule('/?', 'home', array(
    'controller' => 'main', 
    'action' => 'home',
));

$router->rule('/widget/defaultstate', 'widget-state', array(
        'controller' => 'widget',
        'action' => 'defaultState',
));

$router->rule('/widget/:widget-name/?', 'widget', array(
        'controller' => 'widget',
        'action' => 'load',
));

//remove before production!
$router->rule('/:controller/:action/?', 'default');