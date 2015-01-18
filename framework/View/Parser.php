<?php
/**
 * A simple parser class
 *
 * Takes care of all template parsing procedures
 *
 * @author Lyubomir Slavilov <lyubo.slavilov@gmail.com>
 *
 */
namespace Candle\View;

class Parser {

    public function applyModifier($result, $mod)
    {
        $modifiers = array(
            'echo' => 'echo $this->%s',
            'noecho' => '$this->%s',
            '@' => '$this->%s',
            '>' => 'print_r($this->%s)', //skip precommit hook
            '>>' => 'echo \'<pre>\'; print_r($this->%s); echo \'</pre>\'', //skip precommit hook
            'dump' => 'print_r($this->%s)', //skip precommit hook
            'debug' => 'print_r($this->%s)', //skip precommit hook
            'predump' => '<pre>print_r($this->%s)</pre>', //skip precommit hook
            'lower' => 'echo strtolower($this->%s)',
            'upper' => 'echo strtoupper($this->%s)',
            'ucfirst' => 'echo ucfirst($this->%s)',
            'lcfirst' => 'echo lcfirst($this->%s)',
            'ucwords' => 'echo ucwords($this->%s)',
            'lcwords' => 'echo lcwords($this->%s)',
            '_tospace' => 'echo str_replace("_", " ", $this->%s)',
            '_toSpace' => 'echo ucwords(str_replace("_", " ", $this->%s))',
            'inc' => 'echo $this->%s++',
            'json' => 'echo json_encode($this->%s)',
        );

        if (isset($modifiers[$mod])) {
            return sprintf($modifiers[$mod], $result);
        } else {
            return sprintf($modifiers['echo'], $result);
        }

    }


    public function parse($file)
    {

        if (!file_exists($file)) {
            throw new \Exception('Parser error: File does not exists ' . $file);
        }

        $content = file_get_contents($file);
        //variables
        $pattern = "/\{\{\s*([\sa-z0-9_\-\(\)\.\,\'\":\[\]\$\/]+)\s*(\s*\|\s*([a-z0-9>_@]+)\s*)?\}\}/i";
        $parser = $this;
        $content = preg_replace_callback($pattern, function($matches) use ($parser){
            $match = $matches[1];

            if (count($matches) == 4) {
                $mod = $matches[3];
            } else {
                $mod = 'echo';
            }

            $match = str_replace('[', 'array(', $match);
            $match = str_replace(']', ')', $match);
            $match = str_replace(':', '=>', $match);


            $match = preg_replace('/(\'[^\']*)(\.)([^\']*\')/i', '$1[[DOT]]$3', $match);

            $match = preg_replace('/\$this.([a-z0-9_]+)/i', '$this->$1', $match);



            $parts = explode('.', $match);
            $result = array_shift($parts);

            if (is_array($parts)){
                foreach ($parts as $part) {
                     if (substr(trim($part), -1) == ')') {
                         $result .= '->' . $part;
                     } else {
                         $result .= '["' . $part .'"]';
                     }
                }
            }

            $result = str_replace('[[DOT]]', '.', $result);
            $result = '<?php ' . $parser->applyModifier($result, $mod) . '; ?>';
            return $result;
        }, $content);

        return $content;
    }
}
