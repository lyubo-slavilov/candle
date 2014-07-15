<?php
/**
 * Url generator singleton
 *
 * Makes possible auto generation of urls based on the router rules
 *
 * @author Lyubomir Slavilov <lyubo.slavilov@gmail.com>
 *
 */
namespace Candle\Url;

use Candle\Config;

use Candle\Exception\UrlGeneratorException;

class Generator {
    static private $instance;

    /**
     * Singleton factory
     * @return \Candle\Url\Generator
     */
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Generates an URL based on the router rule name
     *
     * @param string $ruleName Rule name
     * @param array $params Parameters to be added in the URL (based on the rule)
     * @param boolean  $absolute Optional. Whether to generate absolute or relative URL. Default NULL.
     * @throws UrlGeneratorException If rule not found
     * @throws UrlGeneratorException If a parameter is missing
     * @return string
     */
    public function generateUrl($ruleName, array $params = array(), $absolute = false)
    {
        $rule = Router::getInstance()->getRouteByName($ruleName);

        if (!$rule) {
            throw new UrlGeneratorException("Unknown rule: {$ruleName}");
        }

        $urlPattern = preg_replace('/[^\p{L}\p{N}\:\/\_\-]/ui', '', $rule->pattern);


        $url = $urlPattern;

        $url = preg_replace_callback('@:([a-zA-Z0-9\_\-]*)@', function($matches) use ($params){
            if (isset($params[$matches[1]])) {
                return $params[$matches[1]];
            } else {
                throw new UrlGeneratorException("Missing parameter: {$matches[1]}");
            }

        }, $url);

        $script = '';
        if (! strpos($_SERVER['SCRIPT_NAME'], 'index.php')) {
            $script = $_SERVER['SCRIPT_NAME'];
        }

        $url = "{$script}$url";
        if ($absolute) {
            $protocol = !empty($_SERVER['HTTPS']) ? "https" : "http";
            $domain = Config::get('app.domain');
            if (empty($domain)) {
                $domain = $_SERVER['HTTP_HOST'];
            }
            $url = "{$protocol}://{$domain}{$url}";
        }

        return $url;
    }
}
