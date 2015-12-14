<?php
/**
 * The Candle bootstrapper
 *
 * The whole framwork symphony is directed here
 *
 * @author Lyubomir Slavilov <lyubo.slavilov@gmail.com>
 *
 */
namespace Candle;

use Candle\Exception\BootstrapException;

use Candle\Exception\InvalidControllerOrActionException;

use Candle\Exception\AutoloaderErrorException;

use Candle\Exception\ControllerForwardException;

use Candle\Http\Request;

use Wick\View;

use Candle\Controller\AbstractController;

use Candle\Exception\Error404Exception;
use Candle\Event\Dispatcher;


class Bootstrap {

    private $controllerName;
    private $actionName;

    private $controller;
    private $isComponentCalling;

    
    private function getAppFqnPath()
    {
        return ucfirst(basename(CANDLE_APP_DIR));
    }
    
    /**
     * Controller factory
     *
     * Creates a controller based on its name
     *
     * @param string $controllerName
     * @throws Error404Exception
     * @throws \Exception
     * @return Candle\Controller\AbstractController
     */
    private function createController($controllerName)
    {
        $appFqn = $this->getAppFqnPath();
        $controllerClassName = $appFqn . '\\Controller\\' . ucfirst($this->controllerName) . 'Controller';


        try {
            $controller = new $controllerClassName();
        } catch (AutoloaderErrorException $ex) {
            throw new InvalidControllerOrActionException('Invalid controller: ' . $controllerClassName);
        }


        if(! $controller instanceof Controller\AbstractController){
            throw new BootstrapException("Controllers must instantiate the AbstractContrtoller");
        }

        return $controller;
    }

    /**
     * Forwards itself to another controller
     * @param ControllerForwardException $forwardException
     * @throws \Exception
     */
    private function forward(ControllerForwardException $forwardException)
    {
        $request = Request::getInstance();
        $forwardChain = $request->getParam('forwardChain', array());
        if (count($forwardChain) > 5) {
            throw new BootstrapException('Too many forwards');
        }

        $forwardChain[] = $this->controller;
        $request->setParam('forwardChain', $forwardChain);
        $request->setParam('forwardParams', $forwardException->getParams());
        $request->setParam('isForwarded', true);
        
        return $this->run($forwardException->getController(), $forwardException->getAction());
    }
    
    
    private function registerWickExtensions()
    {
        
    }

    /**
     * Bootstrap starter
     *
     * @param string $controllerName
     * @param string $actionName
     * @param boolean $isComponentCalling
     * @throws \Candle\Exception\Error404Exception
     * @return rendered content
     */
    public function run($controllerName, $actionName=null, $isComponentCalling = false) {

        $request = Request::getInstance();

        Dispatcher::fire('bootstrap.boot', [
            'controllerName' => $controllerName,
            'actionName' => $actionName,
            'isComponentCalling' => $isComponentCalling,
            'isReboot' => $request->getParam('isForwarded', false),
        ]);
        if (! $isComponentCalling) {
            $this->registerWickExtensions();
        }

        if (is_null($actionName)) {
            list($controllerName, $actionName) = explode('::', $controllerName);
        }

        $this->isComponentCalling = $isComponentCalling;

        $this->controllerName = $controllerName;
        $this->actionName= $actionName;

        $this->controller = $this->createController($controllerName);

        if($isComponentCalling) {
            $actionName .= 'Component';
        } else {
            $actionName .= 'Action';
        }

        if ( ! method_exists($this->controller, $actionName)) {
            $ex = new InvalidControllerOrActionException('Invalid action: ' . $actionName);
            throw $ex;
        }

        try {

            $renderParams = $this->controller->execute($actionName);

            $content = $this->renderResult($renderParams);
            return $content;

        } catch (ControllerForwardException $ex) {

            return $this->forward($ex);
        }

    }

    /**
     * Renders the result from the controller.action execution
     *
     * @param unknown_type $actionParams
     */
    private function renderResult($actionParams = array()) {

        $template = $this->controller->getTemplate();
        $layout =  $this->controller->getLayout();

        if ($template !== false) {
            if ($template == '') {
                $c = strtolower($this->controllerName);
                $t = strtolower($this->actionName);

                if ($this->isComponentCalling) {
                    $template =  CANDLE_APP_DIR . "/View/{$c}/component/{$t}.phtml";
                } else {
                    $template =  CANDLE_APP_DIR . "/View/{$c}/{$t}.phtml";
                }
            }
            
            $view = new View($actionParams);
            $content =  $view->render($template);
        } else {
            $content = $actionParams;
        }

        if ($layout !== false && !$this->isComponentCalling) {

            if($layout != '') {
                $layout = CANDLE_APP_DIR . '/View/' . $layout;
            } else {
                $layout = CANDLE_APP_DIR . '/View/' . Config::get('wick.defaultLayout');
            }
            $layoutView = new View(array(
                    'candle_content' => $content
            ));
            $content = $layoutView->render($layout);

        }
        if (! empty($content)) {
            echo $content;
        }
    }
}
