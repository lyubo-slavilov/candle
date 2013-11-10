<?php

namespace Flame;

use Candle\Config;

use Flame\ORM\AbstractRepository;

use Flame\ORM\Adapter;
/**
 * Candle's ORM service container
 * 
 * @author Lyubomir Slavilov <lyubo.slavilov@gmail.com>
 *
 */
class Flame {
    
   static private $reposiotires = array();
   static private $adapter = null;
   
   
   /**
    * Gets the current adapter instance
    * @return NULL
    */
   static public function getAdapter()
   {
       if (is_null(self::$adapter)) {
           self::$adapter = new Adapter();
           
           $cfg = Config::get('flame');
           $dsn = "{$cfg['driver']}:dbname={$cfg['dbname']};host={$cfg['host']}";
           self::$adapter->connect($dsn, $cfg['user'], $cfg['pass']);
       }
       
       return self::$adapter;
   }
   
   /**
    * 
    * @param string $name
    * @return AbstractRepository
    */
   static public function getRepo($name)
   {
       $fqnPrefix = 'Model\Repository\\';

       $fqn = $fqnPrefix . ucfirst(strtolower($name));
       
       if ( ! isset(self::$reposiotires[$fqn])) {
           self::$reposiotires[$fqn] = new $fqn(self::getAdapter());
       }
       
       return self::$reposiotires[$fqn];
       
   }
}