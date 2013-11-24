<?php
namespace Rad\Controller;

use Service\Utils;

use Candle\Http\Request;

use Candle\Controller\AbstractController;

class CtrlController extends AbstractController {

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
              'methods' => $methodList
          );
    }

}