<?php
/**
 * A simple parser class
 *
 * Takes care of all template parsing procedures
 *
 * @author Lyubomir Slavilov <lyubo.slavilov@gmail.com>
 *
 */
namespace Candle\View;

class Parser {
    
    
    public function parse($file)
    {
        
        if (!file_exists($file)) {
            throw new Exception('Parser error: File does not exists ' . $file);
        }
        
        $content = file_get_contents($file);
        
        //variables
        $pattern = "/\{\{\s*([\sa-z0-9_\-\(\)\.\,\'\":\[\]\$]+)\s*\}\}/i";
        
        $content = preg_replace_callback($pattern, function($matches){
            $match = $matches[1];
            
            $match = str_replace('[', 'array(', $match);
            $match = str_replace(']', ')', $match);
            $match = str_replace(':', '=>', $match);
            
            $match = preg_replace('/\$([a-z0-9_]+)/i', '$this->$1', $match);
            
            $parts = explode('.', $match);
            $result = array_shift($parts);
            
            if (is_array($parts)){
                foreach ($parts as $part) {
                     if (substr(trim($part), -1) == ')') {
                         $result .= '->' . $part;
                     } else {
                         $result .= '["' . $part .'"]';
                     }
                }
            }
            $result = '<?php echo $this->' . $result .'; ?>';
            return $result;
        }, $content);
        
        return $content;
    }
}