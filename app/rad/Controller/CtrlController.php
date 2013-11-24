<?php
namespace Rad\Controller;

use Service\Utils;

use Candle\Http\Request;

class CtrlController extends AbstractAjaxController {

    public function beforeExecute()
    {
        $this->setLayout(false);
    }
    
    public function actionListComponent()
    {
          $params = $this->getRequest()->getParam('component', array());

          $app = Utils::getParam($params, 'app', false);
          $controller = Utils::getParam($params, 'controller', false);
          
          $className = '\\' . ucfirst($app) . '\\Controller\\' . ucfirst($controller) . 'Controller';
            
          $reflection = new \ReflectionClass($className);
          
          $methodList = array(
              'actions' => array(),
              'components' => array()
          );
          
          foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
              if (preg_match('/^(.*)Action$/i', $method->getName(), $match)) {
                  $methodList['actions'][strtolower($match[1])] = $match[1];
              }
              if (preg_match('/^(.*)Component$/i', $method->getName(), $match)) {
                  $methodList['components'][strtolower($match[1])] = $match[1];
              }
          }
          
          ksort($methodList['actions']);
          ksort($methodList['components']);
          
          return array(
              'app' => $app,
              'controller' => strtolower($controller),
              'methods' => $methodList
          );
    }
    
    public function newformAction()
    {
        return array(
            'app' => $this->getRequest()->get('app', false)
        );
    }
    
    public function createAction()
    {
        $params = $this->getRequest()->post('ctrl');
        
        $app = $this->getRequest()->post('app', false);
        $name = Utils::getParam($params, 'name', false);
        $withaction = Utils::getParam($params, 'withaction', false);
        
        if (!$name) {
            $this->stop('Invalid controller name');
        }
        
        $utils = new \Service\Rad\CtrlUtils();
        
        try {
            $utils->createController($app, $name, $withaction);
        } catch (\Exception $e) {
            $this->stop($e->getMessage());
        }
        
        return;
    }
    
    public function actionPropsAction()
    {
        $app = $this->getRequest()->get('app', false);
        $controller = $this->getRequest()->get('ctrl', false);
        $method = $this->getRequest()->get('data', false);
        
        $viewFile = CANDLE_APP_BASE_DIR . '/' . strtolower($app) . '/View/' . strtolower($controller) . '/' . strtolower($method) .'.phtml';
        if (file_exists($viewFile)) {
            return array(
                'file' => strtolower($method) .'.phtml',
                'content' => file_get_contents($viewFile)
            );
        } else {
            return array(
                'file' => false,
                'content' => ''
            );
        }
    }
}