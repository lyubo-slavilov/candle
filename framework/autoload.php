<?php
/**
 * The Candle autoloaders
 */
use Candle\Exception\AutoloaderErrorException;

/**
 * Main autoloader - loads framwork classes and application classes
 */
spl_autoload_register(function ($className) {

    $parts = explode('\\', $className);
    $partso = $parts;
    if (is_array($parts)) {
         
        $class = array_pop($parts);
         
        if ($parts[0] == '') {
            array_shift($parts);
        }
         
        if ($parts[0] == 'Candle') {
            array_shift($parts);
            $baseDir = __DIR__;
        } else {
            $nsPrefix = array_shift($parts);
             
            $baseDir = CANDLE_APP_BASE_DIR . '/' . strtolower($nsPrefix);
        }
         
        $filename = $baseDir . '/' . implode('/', $parts) . '/' . $class . '.php';
         
        if (file_exists($filename)) {
            require $filename;
        } 
    }

});

/**
 * Library autoloader
 */
spl_autoload_register(function($className){
    
    $parts = explode('\\', $className);
    $partso = $parts;
    if (is_array($parts)) {
        
        $class = array_pop($parts);
        
        if ($parts[0] == '') {
            array_shift($parts);
        }
        
        $baseDir = __DIR__ . '/../lib';
        $filename = $baseDir . '/' . implode('/', $parts) . '/' . $class . '.php';
        
        if (file_exists($filename)) {
            require $filename;
        } else {
            require_once __DIR__ . '/Exception/AutoloaderErrorException.php';
            throw new Candle\Exception\AutoloaderErrorException("Class '{$className}' not found");
        }
    }
    
});