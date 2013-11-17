<?php
namespace Rad\Controller;

use Candle\Http\Request;

use Candle\Controller\AbstractController;

class MainController extends AbstractController {

     
    public function homeAction()
    {
        return array();
    }

    public function error404Action()
    {
        $this->setLayout('error_layout.phtml');
        return array(
                'exception' => Request::getInstance()->getParam('exception')
        );
    }
     
    public function error500Action()
    {
        $this->setLayout('error_layout.phtml');
        return array(
                'exception' => Request::getInstance()->getParam('exception')
        );
    }
}