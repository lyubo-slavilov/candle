<?php
namespace Rad\Controller;

use Candle\Http\Request;

use Candle\Controller\AbstractController;

class AppController extends AbstractController {

     
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

}