<?php
namespace Rad\Controller;

use Service\Utils;

use Candle\Http\Request;

class AppController extends AbstractAjaxController {


    public function installedListComponent()
    {
        $glob = glob(CANDLE_APP_BASE_DIR . '/*', GLOB_ONLYDIR);
        $flist = array();
        foreach ($glob as $file) {
            $flist[] = str_replace(CANDLE_APP_BASE_DIR . '/', '',  $file);
        }
        
         return array(
                 'apps' => $flist
         );
    }
    
    
    public function controllersAction()
    {
        $app = $this->getRequest()->get('app');
        
        $glob = glob(CANDLE_APP_BASE_DIR . '/' . $app .'/Controller/*Controller.php');
        $clist = array();
        foreach ($glob as $file) {
            $controller = str_replace(CANDLE_APP_BASE_DIR . '/' . $app .'/Controller/', '',  $file);
            $controller = str_replace('Controller.php', '',  $controller);
            $clist[] = $controller;
        }
    
        return array(
            'app' => $app,
            'controllers' => $clist
        );
    }
    
    public function newformAction()
    {
        return array();
    }
    
    public function createAction()
    {
        $this->setTemplate(false);
        
        $post = $this->getRequest()->post('app');
        
        $name = Utils::getParam($post, 'name', false);
        $fc = Utils::getParam($post, 'fc', false);
        $withdev = Utils::getParam($post, 'withdev', false);
        $description = Utils::getParam($post, 'desc', '');
        
        if (!$name) {
            $this->stop('Invalid application name', 'Invalid data', 400);
        }
        
        $utils = new \Service\Rad\AppUtils();
        try {
            $utils->createApplication($name, $fc, $withdev, $description);
        } catch (\Exception $e) {
            $this->stop($e->getMessage(), 'Invalid data', 400);
        }
        
        return;
    }
}