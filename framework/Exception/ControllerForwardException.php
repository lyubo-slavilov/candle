<?php
/**
 * Controller forward exception
 * The whole forwarding process relies on this exception.
 * It carries additional data for the target controller:action
 * 
 * @author Lyubomir Slavilov <lyubo.slavilov@gmail.com>
 *
 */
namespace Candle\Exception;

class ControllerForwardException extends CandleException {
    
    private $controller;
    private $action;
    private $params;
    
    public function __construct($controller, $action, $params = null)
    {
        $this->controller = $controller;
        $this->action = $action;
        $this->params = $params;
    }
    
    public function getController()
    {
        return $this->controller;
    }

    public function getAction()
    {
        return $this->action;
    }
    
    public function getParams()
    {
        return $this->params;
    }
    
}