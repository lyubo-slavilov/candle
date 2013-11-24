<?php
namespace Sincerely\Controller;

use Candle\Http\Request;

use Candle\Controller\AbstractController;

class MainController extends AbstractController {

     
    public function homeAction()
    {
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