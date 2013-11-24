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

$router->rule('/app/new/?', 'app-new', array(
    'controller' => 'app',
    'action' => 'newform',
));
$router->rule('/app/create/?', 'app-create', array(
        'controller' => 'app',
        'action' => 'create',
));

$router->rule('/app/:app/:action/?', 'app-default', array(
    'controller' => 'app',
));


//remove before production!
$router->rule('/:controller/:action/?', 'default');