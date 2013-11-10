<?php
namespace Demo\Controller;

use Candle\Http\Request;

use Candle\Controller\AbstractController;

class TesterController extends AbstractController {

     
    public function internalErrorAction()
    {
        //This will cause notice for undefined variable 
        //and since we are in strict mode
        //The framework will stop with HTTP 500
        $foo = $bar;
        
        
        return array();
    }

}