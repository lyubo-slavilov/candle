<?php

namespace Service\Welder;

use Candle\Config;
class Assets {
    
    static private $assets = [];
    
    
    private function addAsset($type, $file)
    {
        self::$assets[$type][] = $file;
    }
    
    public function addJs($file)
    {
        $this->addAsset('js', $file);
    }
    public function addCss($file)
    {
        $this->addAsset('js', $file);
    }
    public function src($file)
    {
        $assetsDir = Config::get('app.assetsDir');
        
        return $assetsDir . "/" . $file; 
    }
    
    
    public function img($file)
    {
        $asset = $this->src($file);
        echo "<img src=\"{$asset}\" />";
        
    }
   
    public function js($file) {
        $asset = $this->src($file);
        echo "<script src=\"{$asset}\"></script>";
    }
    
    public function css($file) {
        $asset = $this->src($file);
        echo "<link href=\"{$asset}\" rel=\"stylesheet\" />";
    }
    
    public function flushJs()
    {
        foreach (self::$assets['js'] as $file) {
            $this->js($file);
        }
    }
    public function flushCss()
    {
        foreach (self::$assets['js'] as $file) {
            $this->css($file);
        }
    }
}

