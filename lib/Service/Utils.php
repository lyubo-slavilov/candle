<?php
namespace Service;

/**
 * Candle utilities
 * @author Lyubomir Slavilov <lyubo.slavilov@gmail.com>
 *
 */
class Utils {
    
    /**
     * Gets an element from a array with default fallback
     * 
     * @param array $from The array we are reading from
     * @param mixed $key The key of the element
     * @param mixed $default Default value which will be returned if the $from[$key] element is not presented
     * @return mixed
     */
    static public function getParam(array $from, $key, $default = null) {
        return isset($from[$key]) ? $from[$key] : $default;
    }
}