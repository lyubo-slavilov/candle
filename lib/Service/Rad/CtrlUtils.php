<?php
namespace Service\Rad;

/**
 * Candle Rad Controller service
 * @author Lyubomir Slavilov <lyubo.slavilov@gmail.com>
 *
 */
class CtrlUtils extends AbstractUtils {
    
   
    public function createController($app, $name, $withaction) {
        
        $originalName = $name;
        $name = ucfirst($name);
        
        $appDir = CANDLE_APP_BASE_DIR . '/' . strtolower($app); 
        $controllerDir = $appDir . '/Controller';
        $controllerFile = $controllerDir . '/' . $name . 'Controller.php';
        $viewDir = $appDir . '/View/' . strtolower($name);
        
        if (file_exists($controllerFile)) {
            throw new \Exception('Controller already exists');
        }
        
        if (file_exists($viewDir)) {
            throw new \Exception('Controller\'s view dir already exists');
        }
        
        $template = $withaction ? 'new-controller' : 'new-controller-no-action';
        
        $this->createFromTemplate($controllerFile, $template, array(
            'app_ns' => ucfirst(strtolower($app)),
            'controller' => $name
        ));
        
        mkdir($viewDir);
        if ($withaction) {
            $this->createFromTemplate($viewDir . '/default.phtml', 'action_view', array(
                    'action' => 'default',
                    'controller' => $name
            ));
        }
    }

    public function getMethodInfo($app, $controller, $method, $isComponent = false) {
        
        if ($isComponent) {
            $compDir = 'component/';
            $methodFullName = $method . 'Component';
        } else {
            $compDir = '';
            $methodFullName = $method . 'Action';
        }
        
        $viewFile = CANDLE_APP_BASE_DIR . '/' 
                    . strtolower($app) 
                    . '/View/' . $compDir
                    . strtolower($controller) . '/' 
                    . strtolower($method) .'.phtml';
        
        
        $result = array();
        
        if (file_exists($viewFile)) {
            $result['tpl'] = array(
                'fileName' => strtolower($method) . '.phtml',
                'filePath' => $viewFile,
                'source' => file_get_contents($viewFile)
            );
        }
        
        $className = '\\' . ucfirst($app) . '\\Controller\\' . ucfirst($controller) . 'Controller';
        
        $reflectedClass = new \ReflectionClass($className);
        $reflectedMethod = $reflectedClass->getMethod($methodFullName); 
        
        $fileName = $reflectedMethod->getFileName();
        
        $file = new \SplFileObject($fileName);
        $fileIterator = new \LimitIterator($file, $reflectedMethod->getStartLine() - 1, $reflectedMethod->getEndLine() - $reflectedMethod->getStartLine() + 1);
        $source = '';
        foreach($fileIterator as $line) {
            $source .= $line;
        }
        
        $result['method'] = array(
            'name' => $methodFullName,
            'fileName' => basename($fileName),
            'filePath' => $fileName,
            'source' => $source
        );
        
        return $result;
        
    }
}