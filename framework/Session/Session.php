<?php
/**
 * Simple session abstraction
 *
 * @author Lyubomir Slavilov <lyubo.slavilov@gmail.com>
 *
 */
namespace Candle\Session;

use Candle\Config;
use Candle\Event\Dispatcher;

class Session {

    static private $instance;

    private $sessionStarted = false;


    /**
     * Singleton factory
     * @return \Candle\Session\Session
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
        Dispatcher::fire('session.beforeStart');
        
        $sessionConfig = [
           'name' => Config::get('session.name', 'CANDLE'),
           'lifetime' => Config::get('session.lifetime', 0),
           'path' => Config::get('session.path', '/'),
           'domain' => Config::get('session.domain', ''),
           'secure' => Config::get('session.secure', false),
           'httponly' => Config::get('session.httponly', true),
        ];
        
        $payload = Dispatcher::filter('session.config', $sessionConfig);
        
        session_name($payload->get('name'));
        session_set_cookie_params(
                $payload->get('lifetime'),
                $payload->get('path'),
                $payload->get('domain'),
                $payload->get('secure'),
                $payload->get('httponly')
        );
        session_start();
        
        Dispatcher::fire('session.start');
    }

    public function destroy()
    {
        session_destroy();
        self::$instance = null;
    }
    /**
     * Gets a value from the session
     * @param string $name
     * @param mixed $default Optional. A value to return if this session var is not presented
     * @return unknown
     */
    public function get($name, $default = null)
    {

        if (isset($_SESSION[$name])) {
            return $_SESSION[$name];
        } else {
            if (is_callable($default)) {
                return $default->__invoke();
            }
            return $default;
        }
    }

    /**
     * Sets a session variable
     * @param string $name
     * @param mixed $value
     */
    public function set($name, $value)
    {
        $_SESSION[$name] = $value;
    }

    /**
     * Clears a session variable
     * @param unknown_type $name
     */
    public function clear($name)
    {
        unset($_SESSION[$name]);
    }

    /**
     * Gets the id of the session
     * @return string
     */
    public function getId()
    {
        return session_id();
    }
}
