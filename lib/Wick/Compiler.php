<?php
/**
 * A simple parser class
 *
 * Takes care of all template parsing procedures
 *
 * @author Lyubomir Slavilov <lyubo.slavilov@gmail.com>
 *
 */
namespace Wick;

class Compiler {

    private $modifiers = [];
    public function __construct($modifiers)
    {
        $this->modifiers = $modifiers;
    }
    
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
        
        $modifiers = array_merge($modifiers, $this->modifiers);

        if (isset($modifiers[$mod])) {
            return sprintf($modifiers[$mod], $result);
        } else {
            return sprintf($modifiers['echo'], $result);
        }

    }

    public function compileDots($text)
    {
        $prevChar = '';
        $quotsOpen = false;
        $char = $resultChar = '';
        $compiled = '';
        $arrOpened = false;
        
        for($i=0; $i<strlen($text);$i++){
            
            $char = $text[$i];
            $resultChar = $char;
            switch ($char) {
                case " ": 
                    if (!$quotsOpen) {
                        $resultChar = '';
                    }
                    break;
                case "'": 
                    if($prevChar != '\\') $quotsOpen = !$quotsOpen;
                    break;
                case ".": 
                    
                    switch (true) {
                        case $prevChar == ')' && !$quotsOpen:
                            $resultChar = '->';
                            break;
                        case !$quotsOpen: 
                            if ($arrOpened) {
                                $resultChar = '\'][\'';
                            } else {
                                $resultChar = '[\'';
                                $arrOpened = true;
                                
                            }
                            break;
                    }
                    break;
            }
            $compiled .= $resultChar;
            $prevChar = $char;
        }
        
        if ($arrOpened) {
            $compiled .= '\']';
        }
        return $compiled;
    }
    
    public function compile($file)
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

            $match = $this->compileDots($match);
            
//             $match = str_replace('[', 'array(', $match);
//             $match = str_replace(']', ')', $match);
//             $match = str_replace(':', '=>', $match);



            $match = preg_replace('/\$this.([a-z0-9_]+)/i', '$this->$1', $match);

            $result = '<?php ' . $parser->applyModifier($match, $mod) . '; ?>';
            
            return $result;
        }, $content);

        return $content;
    }
}
