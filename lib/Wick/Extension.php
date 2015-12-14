<?php

namespace Wick;

abstract class Extension {
    abstract public function init();
    
    
    public final function __construct()
    {
        
    }
    
    protected final function registerMethod($name, $method) {
        ExtensionManager::registerMethod ( $name, $method );
    }
    protected final function registerModifier($name, $modifier) {
        ExtensionManager::registerModifier ( $name, $modifier );
    }
}
