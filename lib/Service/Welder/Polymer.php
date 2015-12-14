<?php
namespace Service\Welder;

use Candle\Config;

class Polymer
{
    private static $resources = array();

    private $inflateBuffer = array();
    public $resourceBuffer = array();

    private function calcHash()
    {
        $hash = '';
        foreach (self::$resources as $path) {
            $hash .= crc32($path);
        }

        return crc32($hash);
    }

    public function normalizePath($path) {
        $path = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path);
        $parts = array_filter(explode(DIRECTORY_SEPARATOR, $path), 'strlen');
        $absolutes = array();
        foreach ($parts as $part) {
            if ('.' == $part) continue;
            if ('..' == $part) {
                array_pop($absolutes);
            } else {
                $absolutes[] = $part;
            }
        }
        return implode(DIRECTORY_SEPARATOR, $absolutes);
    }
    /**
     *
     *
     * @author lyubomir.slavilov
     * @param string $path Path to the polymer file relative to the WEB dir
     */
    public function add($path)
    {
        self::$resources[$path] = $path;
    }

    public function inflate($path)
    {
        if (isset($this->inflateBuffer[$path])) {
            $this->inflateBuffer[$path]++;
            return '';
        }

        if (file_exists(CANDLE_WEB_DIR . '/polymer/' . $path)) {
            $base = Config::get('app.basePath');
            $dir = dirname($path);
            $content = file_get_contents(CANDLE_WEB_DIR . '/polymer/' . $path);
            $that = $this;

            //Javascripts
            $content = preg_replace_callback('@<script\s+src="(.*)"\s*>\s*</script>@i', function($matches) use ($that, $dir, $base){
                $normalizedScriptPath = CANDLE_WEB_DIR . '/polymer/' . $dir . '/' . $matches[1];
                if (isset($that->resourceBuffer[$normalizedScriptPath])) {
                    $that->resourceBuffer[$normalizedScriptPath]++;
                    return '';
                } else {
                    $that->resourceBuffer[$normalizedScriptPath] = 1;
                    return '<script>' . file_get_contents($normalizedScriptPath) . '</script>';
                }
            }, $content);

            //CSS
            $content = preg_replace_callback('@<link\s+href="(.*)"\s+rel="stylesheet"\s*>@i', function($matches) use ($that, $dir, $base){
                $normalizedScriptPath = CANDLE_WEB_DIR . '/polymer/' . $dir . '/' . $matches[1];
                if (isset($that->resourceBuffer[$normalizedScriptPath])) {
                    $that->resourceBuffer[$normalizedScriptPath]++;
                    return '';
                } else {
                    $shim = strpos($matches[0], 'shim-shadowdom') !== false ? 'shim-shadowdom' : '';
                    $that->resourceBuffer[$normalizedScriptPath] = 1;
                    return "<style {$shim}> " . file_get_contents($normalizedScriptPath) . "</style>";
                    //return '<link rel="stylesheet" href="' . $normalizedScriptPath .'" ' . $shim .'>';
                }
            }, $content);

            $content = preg_replace_callback('@<link\s+rel="stylesheet"\s+href="(.*)"\s*[^>]*>@i', function($matches) use ($that, $dir, $base){
                $normalizedScriptPath = CANDLE_WEB_DIR . '/polymer/' . $dir . '/' . $matches[1];
                if (isset($that->resourceBuffer[$normalizedScriptPath])) {
                    $that->resourceBuffer[$normalizedScriptPath]++;
                    return '';
                } else {
                    $shim = strpos($matches[0], 'shim-shadowdom') !== false ? 'shim-shadowdom' : '';
                    $that->resourceBuffer[$normalizedScriptPath] = 1;
                    return "<style {$shim}> " . file_get_contents($normalizedScriptPath) . "</style>";
                    //return '<link rel="stylesheet" href="' . $normalizedScriptPath .'" ' . $shim .'>';
                }
            }, $content);

            //Imports
            $content = preg_replace_callback('@<link\s+rel="import"\s+href="(.*)"\s*>@i', function($matches) use ($that, $dir){
                $pathToInflate = $that->normalizePath($dir . '/' . $matches[1]);
                return $that->inflate($pathToInflate);
            }, $content);

            $content = preg_replace_callback('@<link\s+href="(.*)"\s+rel="import"\s*>@i', function($matches) use ($that, $dir){
                $pathToInflate = $that->normalizePath($dir . '/' . $matches[1]);
                return $that->inflate($pathToInflate);
            }, $content);


            $this->inflateBuffer[$path] = 1;
            return $content;
        }
        return '';

    }

    public function flush()
    {

        $this->inflateBuffer = array();

        $welderDir = CANDLE_WEB_DIR . '/polymer/weld';
        $hash = $this->calcHash();

        $cachedFile = 'polymer-'.$hash . '.html';
        $cachedFilePath = $welderDir . '/' . $cachedFile;
        if (!file_exists($cachedFilePath)) {
            $weldedContent = '';
            foreach (self::$resources as $path) {
                $weldedContent .= $this->inflate($path);
            }
//             $weldedContent = print_r($this->inflateBuffer, true) . $weldedContent;
//             $weldedContent = print_r($this->resourceBuffer, true) . $weldedContent;
            file_put_contents($cachedFilePath, $weldedContent);
        }
        $base = Config::get('app.basePath');
        echo "<link rel=\"import\" href=\"{$base}polymer/weld/{$cachedFile}\">";
    }
}
