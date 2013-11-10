<?php
/**
 * Special exception used for forwarding the framwork flow to the error 404 page
 * 
 * When this exceptions is caught by the framework it will generate
 * HTTP 404 status code and will forward the execution to the controller:action (configurable)
 * which will generate the error page
 * 
 * @author Lyubomir Slavilov <lyubo.slavilov@gmail.com>
 *
 */
namespace Candle\Exception;

class Error404Exception extends CandleException {
    
}