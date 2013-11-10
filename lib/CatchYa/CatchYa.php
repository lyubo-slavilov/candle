<?php
namespace CatchYa;

use Candle\Config;

use Candle\Session\Session;

/**
 * Simple textual captcha
 * 
 * Works with all phrases in phrases.txt
 *   
 * @author Lyubomir Slavilov <lyubo.slavilov@gmail.com>
 *
 */
class CatchYa {
    
    
    /**
     * Generates new CatchYa phrase and stores the validation token in the Session
     * 
     * @param boolen $breakInParts Default TRUE. Wether to return the phrase parts or 
     * the whole phrase with {%placeholder%} placeholder.
     *  
     * @return mixed String or Array with two elements containing the phrase head and tail
     */
    static public function generate($breakInParts = true)
    {
        $session = Session::getInstance();

        $cfg = Config::readFile(__DIR__ . '/config.ini');
        
        $fileName = $cfg['CatchYa']['source'];

        $fh = fopen(__DIR__ . '/' . $fileName, 'r');
        
        $lineCount = 0;
        while(!feof($fh)){
          $line = fgets($fh);
          $phrases[] = $line;
          $lineCount++;
        }
        fclose($fh);
        
        $phrase = $phrases[rand(0, $lineCount -1)];
        
        preg_match('/{%(.*)%}/i', $phrase, $match);
        
        $value = $match[1];
        
        
        $session->clear('catchya');
        $session->set('catchya', sha1($session->getId() . $value));
        
        $final = str_replace('{%' . $value .'%}', '{%placeholder%}', $phrase);
        if ($breakInParts){
            return explode('{%placeholder%}', $final);
        } else {
            return $final;
        } 
    }
    
    
    /**
     * Validates a value against the stored CatchYa token
     * 
     * @param mixed $value
     * @return boolean TRUE on success FALSE on failure
     */
    static public function validate($value)
    {
        $session = Session::getInstance();
        
        //if no is set catchya use dummy unique value to prevent false positives
        $catchya = $session->get('catchya', sha1(uniqid()));
        
        return sha1($session->getId() . $value) == $catchya;
    }
}