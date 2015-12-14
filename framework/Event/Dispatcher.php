<?php
namespace Candle\Event;

use Candle\Exception\CandleException;
use Candle\Exception\EventDispatcherException;
class Dispatcher
{

    
    static private $eventTreeLevel = 1;
    static private $debugInfo = '';
    
    
    /**
     * Holds all registered listeners
     * @var array
     */
    static private $listeners = [];

    /**
     * Holds all registered listeners
     * @var array
    */
    static private $filters = [];

    
    
    static private function writeDebug($msg)
    {
        if (CANDLE_ENVIRONMENT == 'dev') {
            self::$debugInfo .= str_repeat('-', self::$eventTreeLevel) . $msg . "\n";
        }
        
    }
    
    static public function start()
    {
        self::writeDebug('START');
    }
    
    static public function renderDebug()
    {
        if (CANDLE_ENVIRONMENT == 'dev') {
            self::writeDebug('END');
            return "<pre>" . self::$debugInfo . "</pre>";
        }
    }
    
    /**
     * Executes a specific listener
     *
     * @author lyubomir.slavilov
     * @param mixed $listener
     * @param Event $event the event that was fired
    */
    static private function executeListener($listener, Event $event)
    {        
        
        if (!is_string($listener) &&  is_callable($listener)) {
            self::writeDebug('listener: anonymous ' . $listener);
            return $listener($event);
        }

        self::writeDebug('listener:  ' . $listener);
        list($class, $method) = explode(':', $listener);

        if (is_subclass_of($class, '\\Candle\\Event\\Subscriber')) {
            $instance = SubscriberManager::get($class);
        } else {
            $instance = new $class();
        }

        return $instance->$method($event);

    }

    /**
     * Executes a specific filter
     *
     * @author lyubomir.slavilov
     * @param mixed $filter
     * @param FilterPayload $payload the payload of the filter
     */
    static private function executeFilter($filter, FilterPayload $payload)
    {

        if (!is_string($filter) &&  is_callable($filter)) {
            return $filter($payload);
        }

        list($class, $method) = explode(':', $filter);

        if (is_subclass_of($class, 'EventSubscriber')) {
            $instance = SubscriberManager::get($class);
        } else {
            $instance = new $class();
        }

        return $instance->$method($payload);

    }

    /**
     * Registeres a new listener
     *
     * @author lyubomir.slavilov
     * @param string $eventName The event name
     * @param mixed $listener - todo comments
     * @param integer $priority - todo comments
     */
    static public function listen($eventName, $handler, $priority = 0)
    {

        if (!isset(self::$listeners[$eventName])) {
            self::$listeners[$eventName] = [];
        }

        self::$listeners[$eventName][] = [
            'priority' => $priority,
            'handler' => $handler
        ];

    }

    /**
     * Registeres a new filter
     *
     * @author lyubomir.slavilov
     * @param string $filterName The filter name
     * @param mixed $handler - The filter handler in format Subscriber:Filter
     * @param integer $priority
     */
    static public function addFilter($fitlerName, $handler, $priority = 0)
    {
        //$priority = PHP_INT_MAX - $priority;
        //TODO implement priority defragmentation


        if (!isset(self::$filters[$fitlerName])) {
            self::$filters[$fitlerName] = [];
        }

        self::$filters[$fitlerName][] = [
            'priority' => $priority,
            'handler' => $handler
        ];
    }

    /**
     * Fires an event
     *
     * @author lyubomir.slavilov
     * @param string $eventName Event to be fired
     * @param array $eventParams Event specific parameters
     */
    static public function fire($eventName, $eventParams= array())
    {

        
        
        self::$eventTreeLevel++;       
        
        if (self::$eventTreeLevel >= 10) {
            throw new EventDispatcherException('Event tree goes to deep ' . self::renderDebug());
        }
        
        self::writeDebug('<b>' . $eventName . '</b>');
        
        $execResult = null;

        if (isset(self::$listeners[$eventName])) {

            $eventListeners = self::$listeners[$eventName];
            
            $insertOrder = $priority = [];
            foreach ($eventListeners as $key=>$value) {
                $insertOrder[$key] = $key;
                $priority[$key] = $value['priority'];
            }

            array_multisort($priority, SORT_DESC, $insertOrder, SORT_ASC, $eventListeners);

            $event = new Event($eventName, $eventParams);
            foreach ($eventListeners as $listener) {
                
                
                $execResult = self::executeListener($listener['handler'], $event);


                if ($execResult === false) {
                    break;
                }

                if ($execResult instanceof Event) {
                    $event = $execResult;
                }
            }
            unset($event);
            
        }
        self::$eventTreeLevel--;
        return $execResult;
    }


    /**
     * Executes a filter
     *
     * @author lyubomir.slavilov
     * @param string $filterName Filter to be executed
     * @param array $payloadParams Payload for filtering
     *
     * @return FilterPayload The filtered payload
     */
    static public function filter($filterName, $payloadParams= array())
    {
        $execResult = null;

        $payload = new FilterPayload($filterName, $payloadParams);
        if (isset(self::$filters[$filterName])) {

            $filters = self::$filters[$filterName];

            $insertOrder = $priority = [];
            foreach ($filters as $key=>$value) {
                $insertOrder[$key] = $key;
                $priority[$key] = $value['priority'];
            }

            array_multisort($priority, SORT_DESC, $insertOrder, SORT_ASC, $filters);


            foreach ($filters as $filter) {
                $execResult = self::executeFilter($filter['handler'], $payload);
                if ($execResult === false) {
                    break;
                }
            }

        }
        return $payload;
    }

    /**
     * Makes sibscription for a particular event subscriber
     *
     * @author lyubomir.slavilov
     * @param string $subscriberName The full class name of the event subscriber
     */
    static public function subscribe($subscriberName)
    {

        $subscriber = SubscriberManager::get($subscriberName);

        $eventList = $subscriber->getEventList();


        foreach($eventList as $eventName => $listeners) {

            if(! is_array($listeners)) {
                $listeners = [$listeners];
            }

            foreach ($listeners as $listener) {
                if(is_string($listener['handler'])) {
                    $handlerName = $subscriberName.':'.$listener['handler'];
                    $handlerExists = false;
                    if (isset(self::$listeners[$eventName])) {
                        foreach (self::$listeners[$eventName] as $existingListener) {
                            if ($existingListener['handler'] == $handlerName) {
                                $handlerExists = true;
                            }
                        }
                    }
                    if (!$handlerExists) {
                        self::listen($eventName, $handlerName, $listener['priority']);
                    }
                } elseif (is_callable($listener['handler'])){
                    throw new CandleException('Adding callable event listener through EventSubscriber is fobidden', 1599);
                }
            }
        }


        $filterList = $subscriber->getFilterList();


        foreach($filterList as $filterName => $filters) {

            if(! is_array($filters)) {
                $filters = array($filters);
            }

            foreach ($filters as $filter) {
                if(is_string($filter['handler'])) {
                    $handlerName = $subscriberName . ':' . $filter['handler'];
                    $handlerExists = false;
                    if (isset(self::$filters[$filterName])) {
                        foreach (self::$filters[$filterName] as $existingFilter) {
                            if ($existingFilter['handler'] == $handlerName) {
                                $handlerExists = true;
                            }
                        }
                    }
                    if (!$handlerExists) {
                        self::addFilter($filterName, $handlerName, $filter['priority']);
                    }
                } elseif (is_callable($filter['handler'])){
                    throw new CandleException('Adding callable filter through EventSubscriber is prohibited', 1599);
                }
            }
        }
    }

    static function unsubscribe($eventName, $handlerName = null)
    {
        if (! is_null($handlerName)) {
            foreach (self::$listeners[$eventName] as $handlerKey => $existingListener) {
                if ($existingListener['handler'] == $handlerName) {
                    unset(self::$listeners[$eventName][$handlerKey]);
                }
            }
        } else {
            unset(self::$listeners[$eventName]);
        }
    }
}
