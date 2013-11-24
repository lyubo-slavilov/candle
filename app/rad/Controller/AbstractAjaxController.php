<?php
namespace Rad\Controller;

use Candle\Controller\AbstractController;

abstract class AbstractAjaxController extends AbstractController {

    protected function stop($content, $status = 'invalid data', $code = 400)
    {
        $this->getResponse()->setStatus($code, $status);
        $this->getResponse()->setContent($content);
        $this->getResponse()->send();
        die();
    }
    
    public function beforeExecute()
    {
        $this->setLayout(false);
    }
}