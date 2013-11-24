<?php
namespace Service\Rad;

/**
 * Candle Rad service
 * @author Lyubomir Slavilov <lyubo.slavilov@gmail.com>
 *
 */
class AppUtils extends AbstractUtils {
    
    
    private function createFC($app, $name, $environment = 'prod')
    {
        
        $ext = substr($name, -4);
        
        if (strtolower($ext) != '.php') {
            $name .= '.php';
        }
        
        if ($environment == 'dev') {
            $name = str_replace('.php', '_dev.php', $name);
        } else {
            $environment = 'prod';
        }
        
        
        $this->createFromTemplate(CANDLE_WEB_DIR . '/' . $name, 'front_controller', array(
            'app' => $app,
            'env' => $environment
        ));
    }
    
    private function createMainController($appName, $appControllerDir)
    {
        
        $this->createFromTemplate($appControllerDir. '/MainController.php', 'main_controller', array(
            'app_ns' => ucfirst(strtolower($appName))
        ));
    }
    
    private function createConfig($appDir, $title, $description)
    {

        $this->createFromTemplate($appDir . '/config.ini', 'app_config', array(
            'title' => $title,
            'desc' => $description,
        ));
    }
    
    private function createViews($appName, $appViewDir)
    {
        $this->createFromTemplate($appViewDir . '/layout.phtml', 'layout');
        
        mkdir($appViewDir . '/main');
        $this->createFromTemplate($appViewDir . '/main/error404.phtml', 'error404');
        $this->createFromTemplate($appViewDir . '/main/error500.phtml', 'error500');
        $this->createFromTemplate($appViewDir . '/main/home.phtml', 'home', array(
            'app' => $appName
        ));
    }
    
    private function createRoutes($appDir)
    {
        $this->createFromTemplate($appDir . '/routes.php', 'routes');
    }
    
    public function createApplication($name, $fc, $withdev, $description) {
        
        $originalName = $name;
        $name = strtolower($name);
        $appDir = CANDLE_APP_BASE_DIR . '/' . $name; 
        
        if (file_exists($appDir)) {
            throw new \Exception('Application already exists');
        }
        $ext = substr($fc, -4);
        if (strtolower($ext) != '.php') {
            $fc .= '.php';
        }
        if (file_exists(CANDLE_WEB_DIR . '/' . $fc)) {
            throw new \Exception('Front controller already exists');
        }
        if ($withdev) {
            $dfc = str_replace('.php', '_dev.php', $fc);
            if (file_exists(CANDLE_WEB_DIR . '/' . $dfc)) {
                throw new \Exception('Front controller already exists');
            }
        }
        
        mkdir($appDir);
        mkdir($appDir . '/Controller');
        mkdir($appDir . '/View');
        mkdir($appDir . '/View/component');
        mkdir($appDir . '/View/partial');
        
        
        
        self::createFC($name, $fc);

        if ($withdev) {
            self::createFC($name, $fc, 'dev');
        }
        
        $this->createConfig($appDir, $originalName, $description);
        $this->createMainController($name, $appDir . '/Controller');
        $this->createViews($name, $appDir . '/View');
        $this->createRoutes($appDir);
    }
}