<?php

namespace Demo\Wick;

use Wick\Extension as BaseExtension;
use Service\Container;

class Extension extends BaseExtension {
    
    public function init() {
        
        $this->registerMethod('polymer', [$this, 'polymer']);
        $this->registerMethod('js', [$this, 'js']);
        $this->registerMethod('css', [$this, 'css']);
        $this->registerMethod('src', [$this, 'src']);
        
    }
    
    public function src($file)
    {
        return Container::get('welder.assets')->src($file);
    }
    public function js($file)
    {
        Container::get('welder.assets')->js($file);
    }
    public function css($file)
    {
        Container::get('welder.assets')->css($file);
    }
        
    public function polymer($element, $path = null)
    {
        if (is_null($path)) {
            $path  = $element;
        }
        $componentPath = "bower_components/{$path}/{$element}.html";
        
        $componentPath =  Container::get('welder.assets')->src($componentPath);
        
        return "<link rel=\"import\" href=\"{$componentPath}\">";
    }
    
}
