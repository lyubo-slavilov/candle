<?php
/**
 * Special exception used for redirecting the client
 *
 * When this exceptions is caught by the framework it will generate
 * HTTP 391 status code and will stop the forward further execution
 *
 * @author Lyubomir Slavilov <lyubo.slavilov@gmail.com>
 *
 */
namespace Candle\Exception;

class HttpRedirectException extends \Exception
{
    private $url;
    
    public function __construct($url)
    {
        $this->url = $url;    
    }
    public function getUrl()
    {
        return $this->url;
    }
    
    
}