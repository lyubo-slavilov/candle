<?php
namespace Service\Rad;

/**
 * Candle Rad service
 * @author Lyubomir Slavilov <lyubo.slavilov@gmail.com>
 *
 */
abstract class AbstractUtils {


    protected function parseTemplate($template, $data)
    {
        $content = file_get_contents(__DIR__ . '/templates/' . $template);
        
        if (count($data) > 0) {
            foreach ($data as $key => $value) {
                $placeholders[] = '%' . strtoupper($key) .'%';
                $replacements[] = $value;
            }
            
            return str_replace($placeholders, $replacements, $content);
        } else {
            return $content;
        }
    }
    
    protected function createFromTemplate($filepath, $template, $data = array())
    {
        $content = $this->parseTemplate($template, $data);
        
        $fh = fopen($filepath, 'w');
        fwrite($fh, $content);
        fclose($fh);
    }
}