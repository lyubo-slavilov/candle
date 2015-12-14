<?php
/**
 * The response singleton
 * 
 * An abstraction for the HTTP response
 * 
 * @author Lyubomir Slavilov <lyubo.slavilov@gmail.com>
 *
 */
namespace Candle\Http;

use Service\Utils;
use Candle\Event\Dispatcher;

class Response {
    
    static private $instance;
    
    
    private $headers = array();
    private $content;
    private $contentType = 'text/html';
    private $charset = 'utf-8';
    private $status;
    
    /**
     * Singleton factory
     * @return \Candle\Http\Response
     */
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
    
        return self::$instance;
    }
    
    public function __construct()
    {
        $this->status = array(
            'message' => 'OK',
            'code' => 200,
        );
    }
    
    /**
     * Sets a cookie value
     * @param string $name Cookie name
     * @param string $value Cookie value
     * @param array $options options for setcookie() function
     */
    public function setCookie($name, $value, array $options = array())
    {
        $expire = Utils::getParam($options, 'expire', 0);
        $path = Utils::getParam($options, 'path', null);
        $domain = Utils::getParam($options, 'domayn', null);
        $secure = Utils::getParam($options, 'expire', false);
        $httponly = Utils::getParam($options, 'expire', false);
        
        setcookie($name, $value, $expire, $domain, $secure, $httponly);
    }
    
    /**
     * Deletes a cookie
     * @param string $name 
     */
    public function clearCookie($name)
    {
        $this->setCookie($name, null, array(
            'expire' => time() - 3600
        ));
    }
    
    /**
     * Stores a HTTP header
     * @param string $header
     */
    public function setHeader($header)
    {
        $this->headers[] = $header;
    }
    
    /**
     * Stores the response HTTP status
     * @param unknown_type $code
     * @param unknown_type $message
     */
    public function setStatus($code, $message)
    {
        $this->status = array(
            'message' => $message,
            'code' => $code
        );
    }
    
    /**
     * Stores the response content type
     * @param string $type MIME formated content type
     */
    public function setContentType($type)
    {
        $this->contentType = $type;
    }
    
    /**
     * Stores the response charset
     * @param unknown_type $charset
     */
    public function setCharset($charset)
    {
        $this->charset = $charset;
    }
    
    /**
     * Sets the response content
     * @param unknown_type $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }
    
    
    /**
     * Sends the collected response
     */
    public function send()
    {
        
        $payload = Dispatcher::filter('response.content', ['content' => $this->content]);
        $content = $payload->get('content');
        
        header("HTTP/1.1 {$this->status['code']}: {$this->status['message']}", true, $this->status['code']);
        foreach ($this->headers as $header) {
            header($header);
        }
        
        header('Content-type: ' . $this->contentType . '; charset=' . $this->charset, true);
        
        echo $content;
            
    }
}