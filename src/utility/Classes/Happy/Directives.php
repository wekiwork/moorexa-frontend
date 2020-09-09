<?php
namespace Happy;

use Lightroom\Templates\Happy\Web\Engines\Interfaces\EngineInterface;

// include injector
include_once 'Injector.php';

/**
 * @package Happy Directives
 * @author Amadi Ifeanyi <amadiify.com>
 */
class Directives implements EngineInterface
{
    // include injector
    use Injector;

    public static $directives = [];
    public static $clearList = [];
    private static $masks = [];
    private static $interpreter;
    private static $instance;

    /**
     * @method EngineInterface setInterpreter
     * @param Interpreter $instance
     * @return void 
     */
    public static function setInterpreter($instance) : void
    {
        // set interperter
        self::$interpreter = $instance;
    }

    /**
     * @method EngineInterface initEngine
     * @param string $content
     * @return string 
     */
    public static function initEngine(string $content) : string
    {
        return self::loadDirective();
    }

    // if binding
    public static function _if($arguments, $attrLine) : string
    {
        return "<?php if($attrLine) { ?>";
    }

    // masking
    public static function _masking(string $target) : string
    {
        $mask = '<!-- #('.$target.') -->';
        self::$masks[$target] = $mask;
        // return mask
        return $mask;
    }

    // else binding
    public static function _else() : string
    {
        return "<?php } else { ?>";
    }

    // end if binding
    public static function _endif() : string
    {
        return "<?php } ?>";
    }

    // end for binding
    public static function _endfor() : string
    {
        return "<?php } ?>";
    }

    // end foreach binding
    public static function _endforeach() : string
    {
        return "<?php } ?>";
    }

    // end while binding
    public static function _endwhile() : string
    {
        return "<?php } ?>";
    }

    // elseif binding
    public static function _elseif($arguments, $attrLine) : string
    {
        return "<?php } elseif ($attrLine) { ?>";
    }

    // foreach binding
    public static function _foreach($arguments, $attrLine) : string
    {
        return "<?php foreach ($attrLine) { ?>";
    }

    // for binding
    public static function _for($arguments, $attrLine) : string
    {
        $attrLine = str_replace(' then', ';', $attrLine);
        $attrLine = str_replace(' and', ';', $attrLine);
        
        return "<?php for ($attrLine) { ?>";
    }

    // push to mask
    public static function pushMask(string $mask, $data)
    {
        // check mask
        if (isset(self::$masks[$mask]))
        {
            // get mask
            $getmask = self::$masks[$mask];

            // get output buffer
            $content = ob_get_contents();

            // clean buffer
            ob_end_clean();

            $content = str_replace($getmask, $data, $content);

            echo $content;
        }
    }

    // while binding
    public static function _while($arguments, $attrLine) : string
    {
        return "<?php while ($attrLine) { ?>";
    }

    // load custom directive
    public static function loadDirective()
    {
        // @var Interperter $instance
        $instance = self::$interpreter;

        // @var array $pendingElse
        $pendingElse = [];

        if (self::hasDirectives($instance->interpolateExternal, $matches, $instance)) :

            if (is_array($matches) && count($matches[0]) > 0) :
            
                $shouldFail = [];

                foreach ($matches[0] as $index => $attr) :
                
                    $cleanAttr = rtrim($attr, '}');
                    $attrName = $matches[1][$index];

                    // get attribute line
                    $removed = false;
                    $avoidShortPHPTags = false;

                    if (preg_match('/^[@]{2}/', $cleanAttr)) $avoidShortPHPTags = true;

                    $attrLine = trim(ltrim($cleanAttr, '@'.$attrName));

                    if (preg_match("/^[(]/", $attrLine)) :
                    
                        $attrLine = preg_replace("/^[(]{1}/", '', $attrLine);

                        if (preg_match("/[)]{1}$/", $attrLine)) :
                        
                            $attrLine = preg_replace("/[)]{1}$/", '', $attrLine);
                            $removed = true;

                        endif;

                    endif;

                    if (preg_match('/[;]$/', $attrLine) !== false) :
                    
                        $attrLine = preg_replace('/[;]$/', '', $attrLine);

                        if (!$removed) $attrLine = preg_replace("/[)]{1}$/", '', $attrLine);

                    endif;

                    $build = '';

                    // get declearation
                    $output = self::getResponse($attrName, $attrLine, $response);

                    $keywords = ['if', 'else', 'elseif'];
                    
                    if (!preg_match("/\s*[{]([\s\S]*?)[?][>]/", $response) && !preg_match('/([<]\?php)/', $response) && !preg_match('/([\?][>])/', $response)) :
                    
                        $attrLine = preg_replace("/^['|\"]$/",'',$attrLine);

                        if (preg_match('/[}]/', $response)) :
                        
                            $build = trim($response);
                            $build = preg_replace("/^['|\"]$/",'',$build);
                        
                        else:
                        
                            $comma = '';

                            if (is_string($attrLine) && strlen($attrLine) > 0) $comma = ',';
                            
                            $build = '<?=\\'.static::class.'::runDirective(true,\''.$attrName.'\''.$comma.$attrLine.')?>';

                            if ($avoidShortPHPTags) $build = '\\'.static::class.'::runDirective(true,\''.$attrName.'\''.$comma.$attrLine.');';

                            $build = preg_replace('/[\']([\$])+(.+?)[\']/', '$1$2', $build);

                        endif;

                        $cleanAttr = trim($cleanAttr);

                        if (!is_null($instance->interpolateExternal)) :

                             $instance->interpolateExternal = str_replace($cleanAttr, $build, $instance->interpolateExternal);
                        endif;
                    
                    else:
                    
                        $output = self::runDirective(false, $attrName, $attrLine, null, $response);

                        if (!is_null($instance->interpolateExternal)) :
                        
                            if ($attrName != 'else'):
                            
                                $instance->interpolateExternal = str_replace(trim($cleanAttr), $response, $instance->interpolateExternal);
                            else:
                            
                                $pendingElse[] = [$cleanAttr, $response];
                            endif;

                        endif;

                    endif;

                endforeach;
                
            endif;

        endif;

        if (count($pendingElse) > 0) :
        
            foreach ($pendingElse as $arr) :
            
                $instance->interpolateExternal = str_replace(trim($arr[0]), $arr[1], $instance->interpolateExternal);
            endforeach;

        endif;

        return $instance->interpolateExternal;
    }

    // get directive response
    public static function getResponse($attrName, $attrLine, &$response)
    {
        if (isset(self::$directives[$attrName]))
        {
            $response = self::$directives[$attrName];

            $func = false;

            if (is_string($response))
            {
                if (strpos($response, '::') === false)
                {
                    if (is_callable($response) || function_exists($response))
                    {
                        $func = true;
                    }
                }
            }
            elseif (is_callable($response) && !is_array($response))
            {
                $func = true;
            }
            

            $ref = null;
            $callMethodFromClass = false;

            if ($func)
            {  
                $ref = new \ReflectionFunction($response);
            }
            else
            {
                $responseString = $response;

                if (is_array($response))
                {
                    list($className, $classMethod) = $response;

                    if (is_string($className))
                    {
                        $responseString = '\\' . $className . '::' . $classMethod;
                    }
                }

                if (is_string($responseString) and strpos($responseString, '::') !== false)
                {
                    $start = substr($responseString, 0, strpos($responseString, "::"));
                    $method = substr($responseString, strpos($responseString, '::')+2);

                    if ($start[0] != '\\')
                    {
                        $start = '\\' . $start;
                    }

                    if (class_exists($start))
                    {
                        $callMethodFromClass = true;
                    }
                    else
                    {
                        $response = '';
                    }
                }
            }

            if (!$callMethodFromClass and isset($className))
            {
                if (is_object($className))
                {
                    $start = $className;
                    $method = $classMethod;
                    $callMethodFromClass = true;
                }
            }

            if ($callMethodFromClass)
            {
                // create reflection class
                $ref = new \ReflectionClass($start);
                $ref = $ref->getMethod($method);
            }

            if (is_object($ref))
            {
                $start = $ref->getStartLine()-1;
                $end = $ref->getEndLine();
                $length = $end - $start;
                $filename = $ref->getFileName();
                $source = file($filename);
                $func = implode("", array_slice($source, $start, $length));

                if (stripos($func, 'return') !== false)
                {
                    // check if directive contains php
                    $hasPhp = stripos($func, '<?php');

                    // check for closing tag
                    if ($hasPhp === false) $hasPhp = stripos($func, '?>');

                    // check for php
                    if ($hasPhp === false)
                    {
                        // get last return
                        $lastReturn = strrpos($func, 'return');
                        $start = substr($func, $lastReturn + strlen('return'));
                        $start = preg_replace('/(\s*)/', '', $start);

                        // get ending curlybrace 
                        $start = substr($start, 0, strrpos($start, '}'));

                        // look for the last statement terminator
                    
                        $end = strrpos($start, ';');
                        $start = substr($start, 0, $end);

                        //$response = trim($start);

                        $response = '';
                    }
                    else
                    {
                        $response = '<?php hasphp(--masking--){ ?>';
                    }
                }
                else
                {
                    $response = '';
                }

                $source = null;
            }
        }
    }

    // add directive
    public static function directive($directiveName, $callbackOrClass)
    {
        self::$directives[$directiveName] = $callbackOrClass;
    }

    // register directives
    public static function register(array $directives, \Closure $closure) : void 
    {
        // merge all
        self::$directives = array_merge(self::$directives, $directives);

        // load instance
        self::$instance = is_null(self::$instance) ? new self : self::$instance;

        // load closure
        call_user_func($closure->bindTo(self::$instance, \get_class(self::$instance)));
    }

    // run directives
    public static function runDirective($called = false, $attrName, $attrLine = '', $class = null, &$output = '')
    {
        if (isset(self::$directives[$attrName]))
        {
            $response = self::$directives[$attrName];

            $args = func_get_args();

            $arguments = array_splice($args, 2);

            $func = false;

            if (is_string($response))
            {
                if (strpos($response, '::') === false)
                {
                    if (is_callable($response) || function_exists($response))
                    {
                        $func = true;
                    }
                }
            }
            elseif (is_callable($response) && !is_array($response))
            {
                $func = true;
            }

            $args = func_get_args();

            $functionArguments = array_splice($args, 2);

            if (count($arguments) > 0)
            {
                foreach ($arguments as $index => $arg)
                {
                    if (is_string($arg))
                    {
                        $arg = preg_replace("/^(['|\"])|(['|\"]$)/", '', trim($arg));
                        $arguments[$index] = $arg;
                    }
                }
            }

            if ($func)
            {   
                $returned = call_user_func_array($response, $functionArguments);

                $output = $returned;

                if (is_string($returned) && strpos($returned, '<?') !== false)
                {
                    if ($called)
                    {
                        return self::evaluateReturn($returned);
                    }
                }
                else
                {
                    return $returned;
                }
            }
            elseif (is_string($response) || is_array($response))
            {
                $responseString = $response;

                if (is_array($response))
                {
                    // get classname and class response
                    list($className, $classMethod) = $response;

                    if (is_string($className))
                    {
                        $responseString = '\\' . $className . '::' . $classMethod;
                    }
                }

                // execute calling method
                $callMethodFromClass = false;

                if (is_string($responseString) and strpos($responseString, '::') !== false)
                {
                    $class = substr($responseString, 0, strpos($responseString, '::'));
                    $method = substr($responseString, strpos($responseString, "::")+2);

                    if (class_exists($class))
                    {
                        $callMethodFromClass = true;
                    }
                }

                if (!$callMethodFromClass and isset($className))
                {
                    if (is_object($className))
                    {
                        $class = $className;
                        $method = $classMethod;
                        $callMethodFromClass = true;
                    }
                }

                if ($callMethodFromClass)
                {
                    $ref = new \ReflectionClass($class);

                    if ($ref->hasMethod($method))
                    {
                        $arguments[1] = !isset($arguments[1]) ? null : $arguments[1];

                        if ($called)
                        {
                            $returned = call_user_func_array([$class, $method], $arguments);
                        }
                        else
                        {
                            $start = $arguments[0];

                            $start = explode(',', $start);

                            foreach ($start as $i => $s)
                            {
                                $s = preg_replace('/[\'|"]/', '', $s);
                                $arguments[$i] = trim($s);
                            }

                            array_pop($arguments);

                            $returned = call_user_func_array([$class, $method], [$arguments, $attrLine]);
                        }
                        
                        $output = $returned;

                        if (is_string($returned) and strpos($returned, '<?') !== false)
                        {
                            if ($called)
                            {
                                return self::evaluateReturn($returned);
                            }
                        }
                        else
                        {
                            return $returned;
                        }
                    }
                }
            }
        }

        return null;
    }

    // evaluate return
    private static function evaluateReturn($returned)
    {
        $keywords = ['if', 'elseif'];

        $res = trim(preg_replace('/(<\?php|<\?=)|(\?>)/', '', $returned));
        $res = preg_replace('/^[}]|[{]$/','',$res);
        $res = preg_replace("/\s*[(]/", '(', $res);
        
        $res = trim($res);
        
        preg_match("/([\S\s]*?)[{|(]/", $res, $command);

        if (count($command) > 0)
        {
            $command = $command[1];
            $keywords = array_flip($keywords);

            if (isset($keywords[$command]))
            {
                $keyword = $command;
                $line = ltrim($res, $keyword);

                $line = preg_replace("/^[(]|[)]$/",'',$line);

                if ($line == '')
                {
                    $line = '0';
                }

                $line = preg_replace("/['|\"]/", '', $line);

                $chs = isset(Bootloader::$currentClass->model) ? Bootloader::$currentClass->model : self::$Mooxes;

                $success = false;

                if ($keyword == 'if')
                {    
                    $success = self::$Mooxes->bindIfStatement($line, $chs);
                }
                elseif ($keyword == 'elseif')
                {
                    $success = self::$Mooxes->bindIfStatement($line, $chs);
                }

                return $success;
            }
        }

        return false;
    }

    // get block
    private static function getBlock($content, $cleanAttr, &$start=null, &$end=null, &$tab=null)
    {
        $contentCopy = $content;
        $startContent = strstr($contentCopy, $cleanAttr);
        $cleanAttr = trim($cleanAttr);
        $quote = preg_quote($cleanAttr);
        preg_match("/(\s{0}(<%:)(.*?))($quote)/", $contentCopy, $m);
        if (isset($m[1]))
        {
            $tabs = $m[1];
            $start = $m[0];

            $block = strstr($content, $start);
            // get end
            $quote = preg_quote($tabs);
            $block = ltrim($block, $start);
            \preg_match("/($quote)[@]([\S]*)/", $block, $m);
            $end = isset($m[0]) ? $m[0] : null;
            
            preg_match("/([\s\S]*?)($end)/", $startContent, $block);
            $getblock = isset($block[0]) ? $block[0] : null;

            $tab = $tabs;

            return $tab . $getblock;
        }
        else
        {
            preg_match("/(\h{0})($quote)/", $contentCopy, $m);
            if (isset($m[0]))
            {
                $start = $m[0];
                $block = strstr($content, $start);
                $block = ltrim($block, $start);

                \preg_match("/(\h{0})[@]([\S]*)/", $block, $m);
                $end = isset($m[0]) ? $m[0] : null;
                
                preg_match("/([\s\S]*?)($end)/", $startContent, $block);
                $getblock = isset($block[0]) ? $block[0] : null;

                $tab = '';

                return $getblock;
            }
        }

        return null;
    }

    // check if document has directive
    private static function hasDirectives(&$content, &$matches = [], &$instance)
    {
        $customDirectives = array_keys(self::$directives);

        if (count($customDirectives) > 0)
        {
            $dir = [[],[]];

            // find directives
            $expression = '/([@|\\\]{1,2}([a-zA-Z0-9_-])([^\n|;]*)+([;|\n]|))/';
            preg_match_all($expression, $content, $directives);

            if (count($directives[0]) > 0)
            {
                // flip directive
                $flipDirective = array_flip($customDirectives); 

                $cleanDirective = [];

                // loop
                foreach ($directives[0] as $index => $directive)
                {
                    if (!preg_match('/[(]/', $directive))
                    {
                        if (strpos($directive, ';') !== false)
                        {
                            $exp = explode(';', $directive);

                            foreach ($exp as $i => $d)
                            {
                                $addterm = $d.';';
                                
                                if (strpos($directive, $addterm) !== false)
                                {
                                    $cleanDirective[] = $addterm;
                                }
                                else
                                {
                                    $cleanDirective[] = $d;
                                }
                            }
                        }
                        else
                        {
                            $cleanDirective[] = $directive;
                        }
                    }
                    else
                    {
                        // extract everything inside the bracket
                        preg_match_all('/([(].*?[)]?.*)(?<=[)])/', $directive, $all);
                        foreach ($all[0] as $x => $bracket)
                        {
                            // hide terminator
                            $replace = $bracket;
                            if (strpos($bracket, ';') !== false)
                            {
                                $bracket = str_replace(';', '^&endTerminator', $bracket);
                            }
                            $directive = str_replace($replace, $bracket, $directive);
                        }

                        // Now get directive
                        preg_match_all('/(([@|\\\](.*?)([;|\n]))|([@|\\\](.*)))/', $directive, $gdirective);
                        foreach ($gdirective[0] as $o => $gd)
                        {
                            if (strpos($gd, '^&endTerminator') !== false)
                            {
                                $gd = str_replace('^&endTerminator', ';', $gd);
                            }

                            $cleanDirective[] = $gd;
                        }
                    }
                }


                $directive = null;

                foreach ($cleanDirective as $i => $directive)
                {
                    if (is_string($directive))
                    {
                        if ($directive[0] == '@')
                        {
                            // get name
                            preg_match("/[@]([a-zA-Z0-9\-\_]+)/", $directive, $a);
                            if (isset($a[1]))
                            {
                                $getName = $a[1];

                                if (isset($flipDirective[$getName]))
                                {
                                    // set
                                    $dir[0][] = $directive;
                                    // set name
                                    $dir[1][] = $getName;
                                }
                                else
                                {
                                    self::$clearList[] = $directive;
                                }
                            }
                        }
                        else
                        {
                            if (substr($directive, 0,2) == '\@')
                            {
                                $before = $directive;
                                $directive = substr($directive, 1);

                                $instance->interpolateContent = str_replace($before, $directive, $instance->interpolateContent);
                            }
                        }
                    }
                    
                }  
            }

            $matches = $dir;

            return true;
        }

        return false;
    }

    // add token to document
    private static function addToken(&$content, $attr)
    {
        $attr = trim($attr);
        $quote = preg_quote($attr);
        preg_match("/([\h]*)($quote)/", $content, $ma);

        if (isset($ma[0]))
        {
            $spaces = $ma[0];
            $quote = preg_quote($attr);
            $spaces = preg_replace("/($quote)$/",'',$spaces);
            $replace = '<'.str_repeat('%:', strlen($spaces));
            $before = strstr($content, $attr);
            $newstring = $before;
            $newstring = \preg_replace("/([\n]{1})($spaces)[@]([\S]*)/", "\n".$replace.'@$3', $newstring);
            $newstring = $replace . $newstring;
            $content = str_replace($before, $newstring, $content);
            $content = \preg_replace("/([<]{1,})[@]/",'@', $content);
        }
    }

    // preload directive
    public static function preload()
    {
        static $preloadFunc;

        if (is_null($preloadFunc))
        {
            $preloadFunc = function(){
                return ' ';
            };
        }

        // get arguments
        $args = func_get_args();

        array_walk($args, function($directive) use (&$preloadFunc){
            $directive = preg_replace('/[@|(|)]/', '', $directive);
            if (!isset(self::$directives[$directive]))
            {
                self::$directives[$directive] = $preloadFunc;
            }
        });
    }
}