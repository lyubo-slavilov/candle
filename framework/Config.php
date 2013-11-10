<?php
/**
 * Simple configurator class
 * 
 * Works with .ini files and introduces the candle config cascade
 * 
 * @author Lyubomir Slavilov <lyubo.slavilov@gmail.com>
 *
 */
namespace Candle;

class Config 
{
    static private $config = array();
    static private $loaded = false;
    
    
    static public function debug()
    {
        print_r(self::$config);
    }
    
    static public function readFile($filename)
    {
        return parse_ini_file($filename, true);
    }
    
    static private function loadFromFile($filename)
    {
        $data = self::readFile($filename);
        
        self::$config = array_replace_recursive(self::$config, $data);
    }
    
    
    static private function load()
    {
        
        self::loadFromFile(__DIR__ . '/config/config.ini');
        self::loadFromFile(CANDLE_APP_BASE_DIR . '/config.ini');
        self::loadFromFile(CANDLE_APP_DIR . '/config.ini');
    }
    
    static public function get($name, $default = null) {
        if (!self::$loaded) {
            self::load();
        }
        
        $nameParts  = explode('.', $name);
        
        $section = array_shift($nameParts);
        
        if (! isset(self::$config[$section])) {
            return $default;
        }
        
        
        
        if (count($nameParts) > 0){
            $key = implode('.', $nameParts);
            
            if (isset(self::$config[$section][$key])) {
                return self::$config[$section][$key];
            } else {
                return $default;
            }
        } else {
            return self::$config[$section];
        }
        
    }
}