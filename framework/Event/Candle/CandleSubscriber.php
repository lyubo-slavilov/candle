<?php
namespace Candle\Event\Candle;

use Candle\Event\Subscriber;
use Candle\Event\Event;
use Candle\Event\Dispatcher;
use Candle\Event\FilterPayload;

class CandleSubscriber extends Subscriber
{

    private function getAppFqnPath()
    {
        return ucfirst(basename(CANDLE_APP_DIR));
    }
    
    public function init()
    {
        $this->addListener('bootstrap.boot', 'onBoot');
        
        
        $this->addFilter('response.content', 'filterResponseContent');
    }


    public function onBoot(Event $event) {
        
        
        if (!$event->get('isComponentCalling') && !$event->get('isReboot')) {
            
            //Register Wick extensions;
            $candleWickExtension = new \Candle\Wick\Extension();
            $candleWickExtension->init();
            
            //Fire candle.extend event to notify other extensions  
            //to do their magic
            Dispatcher::fire('candle.extend');
    
        }
        
    }
    
    public function filterResponseContent(FilterPayload $payload)
    {
//         $content = $payload->get('content', '');
//         if (CANDLE_ENVIRONMENT == 'dev') {
//             $content = str_replace('</body>', Dispatcher::renderDebug() . '</body>', $content);
//         }
//         $payload->set('content', $content);
    }
}
