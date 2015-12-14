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

use Candle\Exception\ConfigException;

class Config
{
    static private $config = array();
    static private $loaded = false;

    static private $keySearchStack = [];
    static public function debug()
    {
        print_r(self::$config); //ignore precommit
    }

    static public function readFile($filename)
    {
        $file = file_get_contents($filename);
        
        $decoded = json_decode($file, true);
        if (json_last_error()) {
            throw new ConfigException("Cant parce {$filename}. Json decode failed with message: " . json_last_error_msg());
        }
        return $decoded;
    }

    static private function loadFromFile($filename)
    {
        $data = self::readFile($filename);
        self::$config = array_replace_recursive(self::$config, $data);
    }


    static private function load()
    {

        self::loadFromFile(__DIR__ . '/config/config.json');
        self::loadFromFile(CANDLE_APP_BASE_DIR . '/config.json');
        self::loadFromFile(CANDLE_APP_DIR . '/config.json');


        //load dev configurations
        if (CANDLE_ENVIRONMENT == 'dev') {
            if (file_exists(CANDLE_APP_BASE_DIR . '/config_dev.json')) {
                self::loadFromFile(CANDLE_APP_BASE_DIR . '/config_dev.json');
            }
            if (file_exists(CANDLE_APP_DIR . '/config_dev.json')) {
                self::loadFromFile(CANDLE_APP_DIR . '/config_dev.json');
            }
        }
        
        self::$config =  json_decode(json_encode(self::$config));

        self::$loaded = true;

    }

    static public function get($name, $default = null) {
        

        if (in_array($name, self::$keySearchStack)) {
            throw new ConfigException("Config key '{$name}' recursion");
        }
        
        if (count(self::$keySearchStack) >= 10) {
            throw new ConfigException("Config key referance chain is too long");            
        }
        
        array_push(self::$keySearchStack, $name);
        
        if (!self::$loaded) {
            self::load();
        }

        $nameParts  = explode('.', $name);

        $node = self::$config;
        
        foreach ($nameParts as $key) {
            if (isset($node->$key)) {
                $node = $node->$key;
            } else {
                array_pop(self::$keySearchStack);
                return $default;
            }
        }
        
        if (is_string($node) && $node[0] == '$') {
            $found = self::get(substr($node, 1), $default);
        } else {
            $found = $node;
        }
        array_pop(self::$keySearchStack);
        return $found;

    }
}
