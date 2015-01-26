<?php
namespace Candle\Controller;

class AjaxResponseException extends \Exception
{
    private $codeMessage;
    public function __construct($message, $code, $codeMessage) {
        parent::__construct($message, $code);
        $this->codeMessage = $codeMessage;
    }
    
    public function getCodeMessage() {
    
        return $this->codeMessage;
    }
}
