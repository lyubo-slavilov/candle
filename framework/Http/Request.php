<?php
/**
 * The request singleton
 * provides an abstraction of a request to the framework
 * 
 * It wraps the main featorues of the HTTP requests
 * 
 * @author Lyubomir Slavilov <lyubo.slavilov@gmail.com>
 *
 */
namespace Candle\Http;

class Request {
    
    const METHOD_GET = 'GET';
    const METHOD_HEAD = 'HEAD';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';
    const METHOD_DELETE = 'DELETE';
    const METHOD_OPTIONS = 'OPTIONS';
    const METHOD_TRACE = 'TRACE';
    const METHOD_CONNECT = 'CONNECT';
    
    static private $instance;
    
    private $get;
    private $post;
    private $file;
    private $cookie;
    private $ipAddress;
    
    private $controller;
    private $action;
    private $params;
    
    /**
     * Singleton factory
     * @return \Candle\Http\Request
     */
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    
    
    
    private function __construct(){
        
        $this->get = $_GET;
        $this->post = $_POST;
        $this->file = $_FILES;
        $this->cookie = $_COOKIE;
        
        $ip  = $_SERVER['REMOTE_ADDR'];
        
//         $parts = explode('.', $ip);
//         $parts[3] = rand(1,255);
//         $ip =  implode('.', $parts);

        $this->ipAddress = $ip;
        
        $this->setParam('uri', $_SERVER['REQUEST_URI']);
        
        $route = str_replace('?' . $_SERVER['QUERY_STRING'], '', $_SERVER['REQUEST_URI']);
        $route = str_replace($_SERVER['SCRIPT_NAME'], '', $route);
        $this->setParam('route', $route);
        
        
    }
    
    /**
     * Gets a parameter from $_GET
     * @param string $name Parameter name
     * @param mixed $default Optional. A default value which will be returned if the parameter is not presented 
     * @return mixed
     */
    public function get($name, $default = null)
    {
        if (isset($this->get[$name])) {
            return $this->get[$name];
        } else {
            return $default;
        }
    }
    
    /**
     * Sets a parameter in the $_GET
     * @param string $name
     * @param mixed $value
     */
    public function setGet($name, $value)
    {
        $this->get[$name] = $value;
    }

    /**
     * Gets a parameter from $_POST
     * @param string $name Parameter name
     * @param mixed $default Optional. A default value which will be returned if the parameter is not presented
     * @return mixed
     */
    public function post($name, $default = null)
    {
        if (isset($this->post[$name])) {
            return $this->post[$name];
        } else {
            return $default;
        }
    }
    
    /**
     * Sets a request system parameter
     * Used for passing data between parts of the framwork
     * 
     * @param string $name
     * @param string $value
     */
    public function setParam($name, $value)
    {
        $this->params[$name] = $value;
    }
    
    
    /**
     * Sets a request system parameter
     * Used for passing data between parts of the framwork
     * 
     * @param string $name Parameter name
     * @param mixed $default Optional. A default value which will be returned if the parameter is not presented
     * @return mixed
     */
    public function getParam($name, $default = null)
    {
        if (isset($this->params[$name])) {
            return $this->params[$name];
        } else {
            return $default;
        }
    }
    
    public function clearParam($name)
    {
         unset($this->params[$name]);   
    }
    
    /**
     * Gets the client IP address
     */
    public function getIpAddress()
    {
        
        return $this->ipAddress;
    }
    
    /**
     * Gets the request HTTP method
     * @return unknown
     */
    public function getMethod()
    {
        return $_SERVER['REQUEST_METHOD'];    
    }
    
    /**
     * Determines if the request HTTP method is POST 
     * @return boolean
     */
    public function isPost()
    {
        return $this->getMethod() == self::METHOD_POST;
    }
    
    /**
     * Determines if the request HTTP method is GET
     * @return boolean
     */
    public function isGet()
    {
        return $this->getMethod() == self::METHOD_GET;
    }
    
    /**
     * Determines if the HTTP request is ajax request
     * @return boolean
     */
    public function isAjax()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }
    
    /**
     * Alias of isAjax()
     * @return boolean
     */
    public function isXmlHttpRequest()
    {
        return $this->isAjax();
    }
    
    /**
     * Gets a cookie value
     * @param string $name
     * @param mixed $default Optional. A default value which will be returned if the parameter is not presented
     * @return mixed
     */
    public function getCookie($name, $default = null)
    {
        if (isset($_COOKIE[$name])) {
            return $_COOKIE[$name];
        } else {
            return $default;
        }
    }
}