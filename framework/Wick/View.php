<?php
/**
 * A simple view class
 *
 * Takes care of all renderings
 *
 * @author Lyubomir Slavilov <lyubo.slavilov@gmail.com>
 *
 */
namespace Candle\View;

use Service\Container;

use Candle\Http\Response;

use Candle\Url\Generator;

use Candle\Http\Request;

use Candle\Bootstrap;

class View {
    private $layout;

    private $rawParams;
    private $params;
    private $template;
    private $request;

    /**
     * Decorates an data value
     */
    private function decorate()
    {
        foreach($this->rawParams as $key => $value)
        {
            if (is_string($value)) {
                $this->params[$key] = htmlentities($value);
            } else {
                $this->params[$key] = $value;
            }
        }
    }

    /**
     * Gets or creates template compiled image
     * @param string $file
     * @return string The path to the cached file
     */
    private function getCachedImage($file)
    {
        $image = str_replace(CANDLE_APP_BASE_DIR, CANDLE_INSTALL_DIR . '/cache', $file);

        if (file_exists($image)) {
            $fileTime = filemtime($file);
            $imageTime = filemtime($image);
            if ($imageTime > $fileTime) {
                return $image;
            }
        }

        $dir = dirname($image);

        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }

        $compiler = new Compiler();
        $parsedContent = $compiler->compile($file);

        file_put_contents($image, $parsedContent);

        return $image;
    }

    public function __construct(array $params)
    {

        $this->request = Request::getInstance();
        $this->rawParams = $params;

        $this->decorate();


    }
    /**
     * Renders the layout
     *
     * @param string $layoutFile
     * @return string
     */
    private function renderLayout($layoutFile)
    {
        ob_start();
        $template = $this->getCachedImage($layoutFile);
        require $template;

        $result = ob_get_clean();

        return $result;
    }

    /**
     * Renders a partial
     * @param string $partialName Partial name (format is controller.action)
     * @param array $params parameters to be passed to the View
     * @return string
     */
    private function renderPartial($partialName, $params = array(), $skipIfMissing = false)
    {
        $parts = explode('.', $partialName);
        $template = CANDLE_APP_DIR . '/View/' . $parts[0] . '/partial/' . $parts[1] . '.phtml';

        if (! file_exists($template)) {
            if ($skipIfMissing) {
                return '';
            } else {
                return "<span style=\"color: red\">Partial '{$partialName}' does not exists</span>";
            }
        }

        $view = new View($params);
        return $view->render($template);

    }

    /**
     * Shorthand method fore easy url generation
     * @param string $ruleName
     * @param array $params
     * @param boolen $absolute
     * @return string
     */
    public function url($ruleName, array $params = array(), $absolute = false, $asTemplate = false)
    {
        return Generator::getInstance()->generateUrl($ruleName, $params, $absolute, $asTemplate);
    }


    public function welder($welder) {
        return $this->service('welder.' . $welder);
    }

    public function service($name, $newInstance = false)
    {
        return Container::get($name, $newInstance);
    }

    /**
     * Renders a component
     * @param string $controller
     * @param string $action
     * @param array $params
     */
    public function renderComponent($controller, $action, $params = array())
    {
        $bootstrap = new Bootstrap();
        Request::getInstance()->setParam('component', $params);
        $result =  $bootstrap->run($controller, $action, true);
        Request::getInstance()->clearParam('component');
    }

    /**
     * Renders a template
     * @param string $template Template file
     * @return string
     */
    public function render($template)
    {

        if (! file_exists($template)) {
            return "<span style=\"color: red\">Template {$template} does not exists</span>";
        }

        $this->template =  $this->getCachedImage($template);

        ob_start();

        require $this->template;

        $result = ob_get_clean();

        return  $result;
    }

    /**
     * Method for checking if a parameter is set
     * @author lyubomir.gavadinov
     * @param string $paramName
     */
    public function __isset($paramName)
    {
        return isset($this->params[$paramName]);
    }

    /**
     * Magic getter for accessing the parameters in the template files
     * @param string $paramName
     * @return string
     */
    public function __get($paramName) {

        $useRaw = false;
        if(strpos($paramName, '_raw')){
            $paramName = str_replace('_raw', '', $paramName);
            $useRaw = true;
        }

        $systemProps = array('candle_content');

        if(in_array($paramName, $systemProps)) {
            $useRaw = true;
        }

        if (isset($this->params[$paramName])) {
            if($useRaw){
                return $this->rawParams[$paramName];
            } else {
                return $this->params[$paramName];
            }
        } else {
            return '';
        }
    }

}
