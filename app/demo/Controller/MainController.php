<?php
namespace Demo\Controller;

use Candle\Http\Request;

use Candle\Controller\AbstractController;
use Candle\Session\Session;
use Service\Container;

class MainController extends AbstractController {

     
    public function defaultAction()
    {
        Session::getInstance()->set('foo', 'bar');
        
        Container::get('welder.assets')->addJs('some.js');
        
        
        return array();
    }

    public function error404Action()
    {
        return array(
                'exception' => Request::getInstance()->getParam('exception')
        );
    }
     
    public function error500Action()
    {
        return array(
                'exception' => Request::getInstance()->getParam('exception')
        );
    }
}