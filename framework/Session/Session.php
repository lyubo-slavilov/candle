<?php
/**
 * Simple session abstraction
 *
 * @author Lyubomir Slavilov <lyubo.slavilov@gmail.com>
 *
 */
namespace Candle\Session;

use Candle\Config;

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
        session_set_cookie_params(0, '/', substr($_SERVER['HTTP_HOST'], strpos($_SERVER['HTTP_HOST'], ".")), false, true);
        session_name(Config::get('app.session_name', 'CANDLE'));
        session_start();
        //TODO throw something on failure
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
