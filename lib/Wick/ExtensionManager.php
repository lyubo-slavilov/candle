<?php

namespace Wick;

class ExtensionManager {
    
    private static $registeredMethods = [];
    private static $registeredModifiers = [];
    
    
    public static function registerMethod($name, $method)
    {
        self::$registeredMethods[$name] = $method;
    }
    
    public static function registerModifier($name, $modifier)
    {
        self::$registeredModifiers[$name] = $modifier;
    }
    
    public static function getMethod($name)
    {
        
        if (!isset(self::$registeredMethods[$name])) {
            return null;
        }
        return self::$registeredMethods[$name];
    }
    
    public static function getRegisteredModifiers()
    {
        return self::$registeredModifiers;
    }
    
}
