<?php

namespace Demo\Subscriber;

use Candle\Event\Subscriber;
use Candle\Event\Event;
use Candle\Event\FilterPayload;

class Main extends Subscriber {
    
    public function init() {
        $this->addListener('candle.extend', 'onCandleExtend');

        $this->addFilter('session.config', 'filterSessionConfig');
    }
    
    public function onCandleExtend(Event $event) {
        
        $extension = new \Demo\Wick\Extension();
        $extension->init();
        
    }
    
    public function filterSessionConfig(FilterPayload $payload)
    {
//         $payload->set('domain', 'fooo');
    }
}