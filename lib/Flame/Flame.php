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
   static private $adapters = array();


   /**
    * Gets the current adapter instance
    * @return NULL
    */
   static public function getAdapter(array $cfg = array())
   {

       if (empty($cfg)) {
           $cfg = Config::get('flame');
       }
       $dsn = "{$cfg['driver']}:dbname={$cfg['dbname']};host={$cfg['host']}";

       if (! isset(self::$adapters[$dsn])) {
           self::$adapters[$dsn] = new Adapter();
           self::$adapters[$dsn]->connect($dsn, $cfg['user'], $cfg['pass']);
       }

       return self::$adapters[$dsn];
   }

   /**
    *
    * @param string $name
    * @return AbstractRepository
    */
   static public function getRepo($name, $connect = true)
   {
       $fqnPrefix = 'Model\Repository\\';

       $fqn = $fqnPrefix . ucfirst(strtolower($name));

       $cfg = Config::get('flame_' . strtolower($name) . '_repository', array());

       if ( ! isset(self::$reposiotires[$fqn])) {
           if ($connect) {
               $adapter = self::getAdapter($cfg);
           } else {
               $adapter = null;
           }
           self::$reposiotires[$fqn] = new $fqn($adapter);
       }

       return self::$reposiotires[$fqn];

   }
}