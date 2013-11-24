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
}