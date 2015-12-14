<?php
namespace Candle\Event;

abstract class Subscriber
{
    private $listenerPool = [];

    private $filterPool = [];

    abstract public function init();

    public function __construct()
    {

        $this->init();
    }
    public function getEventList()
    {
        return $this->listenerPool;
    }

    public function getFilterList()
    {
        return $this->filterPool;
    }

    public function addListener($event, $eventHandler, $priority = 0)
    {
        $this->addToPool('listener', $event, $eventHandler, $priority = 0);
    }

    public function addFilter($event, $eventHandler, $priority = 0)
    {
        $this->addToPool('filter', $event, $eventHandler, $priority);
    }


    private function addToPool($pool, $event, $eventHandler, $priority = 0)
    {

        $poolName = strtolower($pool)  . 'Pool';

        $pool = &$this->{$poolName};
        $pool[$event][] = array(
            'priority' => $priority,
            'handler' => $eventHandler
        );
    }
}
