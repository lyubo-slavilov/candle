<?php
/**
 * Abstractratcion of a controller
 * Provides all necessary logic for any controller in the MVC
 * 
 * @author Lyubomir Slavilov <lyubo.slavilov@gmail.com>
 *
 */
namespace Candle\Controller;

use Candle\Exception\Error404Exception;

use Candle\View\View;
use Candle\Url\Generator;
use Candle\Http\Response;
use Candle\Http\Request;
use Candle\Exception\ControllerForwardException;
use Candle\Exception\HttpRedirectException;

abstract class AbstractController {
    
    private $templateName = '';
    private $controllerName = '';
    private $layoutName = '';
    
    /**
     * Quck request access
     * Getter for easy request intance access
     * @return \Candle\Http\Request
     */
    protected function getRequest()
    {
        return Request::getInstance();
    }
    
    /**
     * Quck response access
     * Getter for easy response intance access
     * 
     * @return \Candle\Http\Response
     */
    protected function getResponse()
    {
        return Response::getInstance();
    }
    
    /**
     * Before execute entry point
     * 
     * Child classes could extend this method in order to add logic 
     * before the real action execution 
     */
    public function beforeExecute()
    {}
    
    
    /**
     * Shorthand method for easy url generation
     * 
     * @param string $ruleName
     * @param array $params
     * @param boolean $absolute Default false
     * @return string The generated url
     */
    protected function generateUrl($ruleName, array $params = array(), $absolute = false)
    {
        return Generator::getInstance()->generateUrl($ruleName, $params, $absolute);
    }
    
    /**
     * Renders a template
     * 
     * @param array $params Array of parameters
     * @param string $template The name of the template.   
     * @param string $controllerName Optional. If passed the template will be searched 
     * in the view related with this controller
     * @return string Rendered content
     */
    protected function renderTemplate($params, $template, $controllerName = '')
    {
        $oldTemplate = $this->templateName;
        $oldController = $this->controllerName;
        
        $this->setTemplate($template, $controllerName);
        $v = new View($params);
        
        $result =  $v->render($this->getTemplate());
        
        $this->templateName = $oldTemplate;
        $this->controllerName = $oldController;
        
        return $result;
    }
    
    protected function renderComponent($controllerName, $action, $params = array())
    {
        $oldTemplate = $this->templateName;
        $oldController = $this->controllerName;
    
        $this->setTemplate($template, $controllerName);
        $v = new View($params);
    
        $result =  $v->renderComponent($controller, $action, $params);
    
        $this->templateName = $oldTemplate;
        $this->controllerName = $oldController;
    
        return $result;
    }
    
    /**
     * Executes an action
     * @param string $actionName
     * @return array The action resultant array
     */
    public final function execute($actionName)
    {
        
        $this->beforeExecute();
        
        $result = $this->$actionName();
        
        $result = $this->afterExecute($result);
        
        return $result;
    }
    
    /**
     * After execute entry point
     * 
     * Child classes could extend this method in order to add logic 
     * after the real action execution 
     * @return array The action resultant array
     */
    public function afterExecute($result)
    {
        return $result;
    }
    
    
    /**
     * Sets a new template for automatic rendering
     * @param string $template
     * @param string $controllerName
     */
    public function setTemplate($template, $controllerName = '')
    {
        $this->templateName = $template;
        
        if($controllerName != '') {
            $this->controllerName = $controllerName;
        }
    }
    
    /**
     * Getter for the template file name
     * @return mixed If template name is setted during the action execution
     * its corresponding file will be returned
     */
    public function getTemplate()
    {
        if ($this->templateName !== '' && $this->templateName !== false) {
            if ($this->controllerName != '') {
                $c = $this->controllerName;
            } else {
                $classList = explode('\\', get_class($this));
                $class = array_pop($classList);
                $c = str_replace('controller', '', strtolower($class));
            }
            $t = $this->templateName;
            
            return CANDLE_APP_DIR . "/View/{$c}/{$t}.phtml";
        } else {
            return $this->templateName;
        }
        
    }
    
    /**
     * Sets a lyout 
     * @param unknown_type $layoutName
     */
    public function setLayout($layoutName)
    {
         $this->layoutName = $layoutName;   
    }
    
    /**
     * Gets the layout
     * @return string
     */
    public function getLayout()
    {
        return $this->layoutName;
    }
    
    /**
     * Starts the redirection process
     * @param string $url The target URL
     * @throws HttpRedirectException
     */
    public function redirect($url)
    {
        throw new HttpRedirectException($url);
    }
    
    /**
     * Starts the forwarding process
     * @param string $controller The target controller 
     * @param string $action The target action
     * @param array $params Optional
     * @throws ControllerForwardException
     */
    public function forward($controller, $action, $params = null)
    {
        throw new ControllerForwardException($controller, $action, $params);
    }
    
    /**
     * Raises a new 404 error
     * @param string $message
     * @throws Error404Exception
     */
    public function raise404Error($message = 'NOT FOUND')
    {
        throw new Error404Exception($message);
    }
    
}
