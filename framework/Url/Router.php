<?php
/**
 * An url router
 *
 * Resolves the controller:action name based on a list of router rules
 *
 * It also could redirect the client before any action (configurable)
 *
 * @author Lyubomir Slavilov <lyubo.slavilov@gmail.com>
 *
 */
namespace Candle\Url;

use Candle\Config;

use Candle\Http\Request;

class Router {
    static private $instance;

    private $rules;

    /**
     * Singleton factory
     * @return \Candle\Url\Router
     */
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
        $this->rules = array();
    }

    /**
     * Gets a route by name
     * @param string $name
     * @return mixed Route data object if route exists. FALSE otherwise.
     */
    public function getRouteByName($name)
    {
        if (isset($this->rules[$name])) {
            return $this->rules[$name];
        } else {
            return false;
        }
    }

    /**
     * Adds a route data object in the routes list
     *
     * @param string $pattern Pattern of the route
     * @param string $name Name of the route/rule
     * @param array $options Route parsing parameters
     */
    public function rule($pattern, $name, array $options = array(), $exportable = false)
    {
        $rule = new \stdClass();

        $rule->exportable = $exportable;

        $baseRoute = Config::get('app.base_route', '');
        $pattern = $baseRoute . $pattern;
        $lastChar = substr($pattern, -1);

        $rule->name = $name;
        $rule->pattern = $pattern;
        $rule->controller = isset($options['controller']) ? $options['controller'] : null;
        $rule->action = isset($options['action']) ? $options['action'] : null;

        unset($options['controller']);
        unset($options['action']);

        $rule->params = $options;

        $this->rules[$name] = $rule;
    }


    public function export()
    {
        $export = array();

        foreach ($this->rules as $name => $rule) {
            if ($rule->exportable) {
                $export[$name] = $rule;
            }
        }

        return $export;
    }


    /**
     * Processes a pattern
     *
     * Extracts all :some_name arguments from the pattern
     *
     * @param string $pattern
     * @return array Array with extracted arguments
     */
    private function processPattern(&$pattern)
    {
        $args = array();
        $pattern = preg_replace_callback('@(:[a-zA-Z0-9\_\-]*)@', function($matches) use (&$args) {

            $args[count($args)+1] = $matches[1];
            return '([^/]*)';
        }, $pattern);

        return $args;
    }


    /**
     * Checks if a pattern matches an url route
     * @param string $pattern
     * @param string $route
     * @return boolean
     */
    private function match($pattern, $route)
    {
        $result =  preg_match("@^{$pattern}$@", urldecode($route), $matches);

        if ($result) {
            return $matches;
        } else {
            return false;
        }
    }

    /**
     * Tries to redirect the client according to the [redirect] config section
     */
    public function tryRedirect()
    {
        $protocol = !empty($_SERVER['HTTPS']) ? "https" : "http";
        $domain = $_SERVER['HTTP_HOST'];
        $uri = $_SERVER['REQUEST_URI'];
        $url = $domain . '/' .$uri;


        $redirects = Config::get('redirect');

        foreach ($redirects as $from => $to) {

            if (preg_match('/^' . $from .'$/i', $domain)) {
                header('Location: ' . $protocol . '://' . $to .  $uri);
                die();
            }
        }
    }

    /**
     * Resolves the controller.action from and in the current request
     *
     *
     * @param Request $request
     * @return Request|boolean
     */
    public function resolve(Request $request)
    {
        $this->tryRedirect();

        $route = $request->getParam('route');

        foreach ($this->rules as $rule) {
            $pattern = $rule->pattern;
            $args = $this->processPattern($pattern);
            $matches = $this->match($pattern, $route);
            if ($matches) {

                if (count ($args) > 0) {
                    foreach($args as $idx=>$arg) {
                        $arg = str_replace(':', '', $arg);

                        $setAsParam = in_array($arg, array('controller', 'action'));

                        if ($setAsParam){
                            $request->setParam($arg, $matches[$idx]);
                        } else {
                            $request->setGet($arg, $matches[$idx]);
                        }

                        if (!is_null($rule->controller) && is_null($request->getParam('controller', null))) {
                            $request->setParam('controller', $rule->controller);
                        }

                        if (!is_null($rule->action) && is_null($request->getParam('action', null))) {
                            $request->setParam('action', $rule->action);
                        }
                    }
                } else {
                    $request->setParam('controller', $rule->controller);
                    $request->setParam('action', $rule->action);
                }

                foreach ($rule->params as $name => $value) {
                    $request->setParam($name, $value);
                }

                return $request;
            }
        }

        return false;
    }

}
