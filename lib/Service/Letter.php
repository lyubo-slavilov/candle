<?php
namespace Service;

class Letter {
    
    public static function publishLetter(\Model\Entity\Letter $letter)
    {
        $letter->setPublishedOn(date('Y-m-d H:i:s', time()));
        $letter->setStatus('PUBLISHED');
        $letter->save();
    }

    public static function rejectLetter(\Model\Entity\Letter $letter)
    {
        $letter->setStatus('REJECTED');
        $letter->save();
    }
    
    
    
}