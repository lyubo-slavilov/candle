<?php
namespace Candle\Event;
/**
 * Class representing an event
 * @author lyubomir.slavilov
 */
class Event
{
    private $eventName;
    private $params;

    public function __construct($eventName, $params) {
        $this->eventName = $eventName;
        $this->params = $params;
    }

    public function getEventName()
    {
        return $this->eventName;
    }

    public function get($paramName, $default = null) {

        if (isset($this->params[$paramName])) {
            $result = $this->params[$paramName];
        } else {
            $result = $default;
        }
        return $result;
    }

    public function set($paramName, $paramValue) {
        $this->params[$paramName] = $paramValue;
    }
}
