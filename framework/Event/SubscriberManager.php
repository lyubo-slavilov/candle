<?php
namespace Candle\Event;
use Candle\Exception\CandleException;
/**
 * Implements event subscriber pool in which all event subscribers live
 *
 * @author lyubomir.slavilov
 */
class SubscriberManager
{
    static private $instances;

    /**
     * Gets an event subscriber from the pool.
     * If needed the subscriber will be instantiated first
     *
     * @author lyubomir.slavilov
     * @param string $name The ful class name of the event subscriber
     * @throws CandleException
     * @return \Candle\Event\Subscriber
     */
    static public function get($name)
    {
        if (isset(self::$instances[$name])) {
            $subscriber = self::$instances[$name];
        } else {

            if (class_exists($name)) {
                if (is_subclass_of($name, '\\Candle\\Event\\Subscriber')) {
                    $subscriber = new $name();
                    self::$instances[$name] = $subscriber;
                } else {
                    throw new CandleException("{$name} must extends Candle\\Event\\Subscriber");
                }
            } else {
                throw new CandleException("Unknown event subscriber {$name}");
            }
        }

        return $subscriber;
    }

    static public function getInstanceSet()
    {
        return self::$instances;
    }
}

