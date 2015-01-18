<?php
namespace Service;

class Container
{
    private static $services;

    public static function get($serviceName, $newInstane=false)
    {


        if (isset(self::$services[$serviceName]) && !$newInstane) {

            return self::$services[$serviceName];
        }

        $className = ucwords(str_replace('.', ' ' , $serviceName));
        $className = 'Service\\' . str_replace(' ' , '\\', $className);

        $service = new $className();
        self::$services[$serviceName] = $service;

        return $service;
    }
}

