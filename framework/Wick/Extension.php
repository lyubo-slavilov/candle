<?php

namespace Candle\Wick;

use Wick\Extension as BaseExtension;
use Candle\Url\Generator;
use Service\Container;
use Candle\Config;

class Extension extends BaseExtension {
    
    public function init() {
        
        $this->registerMethod('url', [$this, 'url']);
        $this->registerMethod('config', [$this, 'config']);
        $this->registerMethod('service', [$this, 'service']);
        $this->registerMethod('welder', [$this, 'welder']);
    }
    
    
    /**
     * Shorthand method fore easy url generation
     * @param string $ruleName
     * @param array $params
     * @param boolen $absolute
     * @return string
     */
    public function url($ruleName, array $params = [], $absolute = false, $asTemplate = false) {
        return Generator::getInstance()->generateUrl($ruleName, $params, $absolute, $asTemplate);
    }
    
    public function service($name, $newInstance = false)
    {
        return Container::get($name, $newInstance);
    }
    
    public function welder($welder) {
        return $this->service('welder.' . $welder);
    }
    
    public function config($name, $default = null)
    {
        return Config::get($name, $default);
    }
}
