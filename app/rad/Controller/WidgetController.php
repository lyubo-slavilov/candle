<?php
namespace Rad\Controller;

use Candle\Http\Request;

use Candle\Controller\AbstractController;

class WidgetController extends AbstractController {

    public function beforeExecute()
    {
        $this->setTemplate(false);
        $this->setLayout(false);
    }
    
    public function loadAction()
    {
        
        $widget = $this->getRequest()->get('widget-name');
        $this->setTemplate('widget/'.$widget);
        
        $result = array();
        
        $method = 'processWidget' . ucfirst($widget);
        if (method_exists($this, $method)) {
            $result = $this->$method();
        }
        return $result;
    }
    
    public function defaultStateAction()
    {
        $this->setTemplate('default-state');
        $this->getResponse()->setContentType('text/json');
        return array();
    }
    
}