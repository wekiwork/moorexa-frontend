<?php
namespace Lightroom\Templates\Happy\Web;

use ReflectionException;

/**
 * @package Happy template engine interpreter
 * @author Amadi Ifeanyi <amadiify.com> 
 */
class Interpreter
{
    /**
     * @var string $interpolateContent
     */
    public $interpolateContent = '';

    /**
     * @var bool $interpolateString
     */
    public $interpolateString = true;

    /**
     * @var array $selfClosing
     */
    public $selfClosing = [
        'img',
        'meta',
        'link',
        'hr',
        'source',
        'area',
        'base',
        'br',
        'col',
        'embed',
        'input',
        'param',
        'track',
        'wbr'
    ];

    /**
     * @var array $yieldUsed
     */
    public static $yieldUsed = [];

    /**
     * @var array $engines
     */
    public static $engines = [];

    /**
     * @var array $externalConfiguration
     */
    public static $externalConfiguration = [];

    /**
     * @var InterpreterAttributes $interpreterAttribute
     */
    private static $interpreterAttribute = null;

    /**
     * @var Interpreter $instances
     */
    private static $instances = null;

    /**
     * @var string $block
     */
    private $block = "";

    /**
     * @var array $styles
     */
    private $styles = [];

    /**
     * @method Interpreter loadAttributes
     * @param string $content
     * @return void
     *
     * This method loads all attributes from content
     * @throws ReflectionException
     */
    public function loadAttributes(string $content) : void
    {
        // load InterpreterAttributes class
        if (self::$interpreterAttribute == null) self::$interpreterAttribute = new InterpreterAttributes;

        // update content
        self::$interpreterAttribute->interpolateExternal = $content;

        if (count(InterpreterAttributes::$attributes) == 0) :

            // create reflection class
            $reflection = new \ReflectionClass(self::$interpreterAttribute);    

            // get class methods
            foreach ($reflection->getMethods() as $attribute) :

                if ($attribute->isPublic() && $attribute->getDeclaringClass()->getName() == InterpreterAttributes::class) :

                    // add attribute
                    InterpreterAttributes::$attributes[] = $attribute->getName();

                endif;

            endforeach;

        endif;

        // load attributes
        foreach (InterpreterAttributes::$attributes as $attribute) :

            // call method
            call_user_func([self::$interpreterAttribute, $attribute]);

        endforeach;

        // update interpolateContent
        $this->interpolateContent = self::$interpreterAttribute->interpolateExternal;

    }

    // string has variables
    public function stringHasVars(&$data, $templateEngine, $single = false, &$privateDump = [])
    {
        // search for binds
        preg_match_all('/({[\s\S]*?})/m', $data, $matches);

        if (isset($_SERVER['SCRIPT_FILENAME']))
        {
            $root = rtrim($_SERVER['SCRIPT_FILENAME'], basename($_SERVER['SCRIPT_FILENAME']));
        }
        else
        {
            $root = '';
        }

        if (count($matches) > 0 && count($matches[0]) > 0)
        {
            foreach ($matches[0] as $a => $m)
            {
                if (substr($m, 0, 2) != '{{')
                {
                    $brace = trim($m);
                    $m = ltrim($m, '{');
                    $m = rtrim($m, '}');
                    $m = trim($m);

                    if (!preg_match('/([\S]*?)[:]\s*\S+/', $m))
                    {
                        // interpolate functions
                        $m = $this->interpolateFunc($m, $templateEngine);

                        if (preg_match('/^(\$this->)/', $m))
                        {
                            $_var = null;
                            $string = $this->loadThis($m, $templateEngine, '$this->', $_var);

                            if (!method_exists($templateEngine, $_var) && !property_exists($templateEngine, $_var))
                            {
                                if ($string === null)
                                {
                                    if (isset(self::$templateEngineInstances['templateEngine']))
                                    {
                                        $_templateEngine = self::$templateEngineInstances['templateEngine'];
                                        // remove the last
                                        array_pop($_templateEngine);
                                        // start from behind
                                        $ctotal = count($_templateEngine) -1;
                                        $keys = array_keys($_templateEngine);
                                        for($i=$ctotal; $i!=-1; $i--)
                                        {
                                            $_templateEngine_ = $_templateEngine[$keys[$i]];
                                            $_templateEngine_->props = $templateEngine->props;
                                            $da = $this->loadThis($m, $_templateEngine_);
                                            if ($da !== null)
                                            {
                                                $string = $da;
                                                $_templateEngine_ = null;
                                                $da = null;

                                                break;
                                            }
                                        }
                                        $keys = null;
                                        $ctotal=null;
                                        $_templateEngine = null;
                                    }
                                }
                            }
                        }
                        else
                        {
                            if (substr($m, 0,1) == '$')
                            {
                                $var = ltrim($m, '$');
                                $ref = new \ReflectionClass($templateEngine);
                                $___templateEngineName = $ref->getName();

                                $seen = false;

                                if (isset(self::$variables[$___templateEngineName]))
                                {
                                    $vars = self::$variables[$___templateEngineName];
                                    if (isset($vars[$var]))
                                    {
                                        $string = $vars[$var];
                                        $seen = true;
                                    }
                                    else
                                    {
                                        if (strpos($var, '->') !== false)
                                        {
                                            $hasdash = substr($var, 0, strpos($var, '-'));

                                            if (isset($vars[$hasdash]))
                                            {
                                                $data = $vars[$hasdash];
                                                $privateDump[0] = $data;

                                                $exp = explode('->', $var);
                                                $string = $data;

                                                unset($exp[0]);

                                                foreach($exp as $i => $chain)
                                                {
                                                    if (strpos($chain, '(') !== false)
                                                    {
                                                        $func = $this->loadFunc($chain, $data, true);
                                                        $string = $func;
                                                        $privateDump[1] = $chain;
                                                        $seen = true;
                                                    }
                                                    else
                                                    {

                                                        if (strpos($chain, '[') === false)
                                                        {
                                                            $string = $data->{$chain};
                                                            $seen = true;
                                                        }
                                                    }
                                                }
                                            }
                                            else
                                            {
                                                $string = '<php-var>'.$m.'</php-var>';
                                            }
                                        }
                                    }
                                }
                            }
                            else
                            {
                                if (preg_match('/([\S]*?)\s*[(]/', $m))
                                {
                                    $string = $this->loadFunc($m, $templateEngine);
                                }
                                else
                                {
                                    $string = $m;
                                }
                            }

                        }

                        if (!$single)
                        {
                            $string = is_array($string) ? json_encode($string) : $string;

                            // replace every encapsulation with it's equivalent data
                            $data = str_replace($brace, $string, $data);
                        }
                        else
                        {
                            $data = $string;
                        }
                    }
                }
            }
        }
    }

    /**
     * @method Interpreter replaceBinds
     * @param array $binds
     * @return void
     * 
     * This method replaces binds with real php tags
     */
    public function replaceBinds(array $binds) : void 
    {
        if (count($binds) > 0 && count($binds[0]) > 0) :
        
            foreach ($binds[0] as $bind) :
            
                if (substr($bind, 0, 2) != '{{') :
                
                    // @var string $replace
                    $replace = trim($bind);

                    // trim bind
                    $bind = trim(rtrim(ltrim($bind, '{'), '}'));

                    if (preg_match("/^(([\$][\S]+)|([\S]*?[(]))/", $bind)) :
                    
                        // @var string $tag
                        $tag = '=';

                        if (preg_match('/[;]$/', $bind)) $tag = 'php ';

                        // update interpolate content
                        $this->interpolateContent = str_replace($replace, '<?'.$tag.$bind.'?>', $this->interpolateContent);

                    endif;

                endif;

            endforeach;
        
        endif;
    }

    /**
     * @method Interpreter getblock
     * @param string $html
     * @param string $tag
     * @param string $tagName
     * @return string
     * 
     * This method returns an html tag block
     */
    public function getblock(string $html, string $tag, string $tagName) : string 
    {
        // start from the first occurrence of $tag
        $html = strstr($html, $tag);
        $html = substr($html, strlen($tag));

        // @var array $replace
        $replace = [];
        
        // get tag block
        $tagBlock = $this->__getblock($html, $tag, $tagName, $replace);

        // get end tag now
        $closingTag = strpos($tagBlock, "</$tagName>");

        // @var string $endline
        $endline = substr(trim($tag),-2);

        $tagInLowerCase = strtolower($tagName);
        $selfclosing = array_flip($this->selfClosing);

        // update block
        $block = $tag . substr($tagBlock, 0, $closingTag);

        // tag has a closing tag
        if ($endline != '/>' && !isset($selfclosing[$tagInLowerCase])) :
        
            // update block
            $block = $tag . substr($tagBlock, 0, $closingTag) . "</$tagName>";

            // @var array $subTagReplace
            $subTagReplace = [];

            // get tag block
            $tagBlock = $this->__getblock($block, $tag, $tagName, $subTagReplace);

            // get closing tag
            $closingTag = strpos($tagBlock, "</$tagName>");

            // tag really has a closing tag
            if ($closingTag !== false) $tagBlock = substr($tagBlock, 0, $closingTag);

            // replace hash
            foreach ($subTagReplace as $hash => $_block) $tagBlock = str_replace($hash, $_block, $tagBlock);

            // update block
            $block = $tagBlock;

        endif;

        // check if we hashed some blocks, then we replace them
        if (count($replace) > 0) :
        
            // replace hash
            foreach ($replace as $hash => $_block) $block = str_replace($hash, $_block, $block);

        endif;

        // return string
        return $block;
    }

    /**
     * @method Interpreter __getblock
     * @param string $html
     * @param string $tag 
     * @param string $tagName
     * @param array $replace
     * @return string
     * 
     * This is a recursive method for get block
     */
    public function __getblock(string $html, string $tag, string $tagName, array &$replace = []) : string
    {
        // @var array $closeTag
        $closeTag = strpos($html, "</$tagName>");

        // continue if tag has a closing tag
        if ($closeTag !== false) :
        
            // @var string $beforecloseTag
            $beforecloseTag = substr($html, 0, $closeTag + strlen("</$tagName>"));

            // find starting tag
            $startingTag = strpos($beforecloseTag, "<$tagName");

            if ($startingTag !== false) :
            
                // @var string $block (get the tag block)
                $block = substr($beforecloseTag, $startingTag);

                // @var string $hash
                $hash = '{'.md5($block).'}';

                // @var string $replacement
                $replacement = $beforecloseTag;

                // update block before closing tag
                $beforecloseTag = str_replace($block, $hash, $beforecloseTag);

                // update replaces
                $replace[$hash] = $block;

                // update html
                $html = str_replace($replacement, $beforecloseTag, $html);

                // call method again
                $html = $this->__getblock($html, $tag, $tagName, $replace);

            endif;

        endif;

        // return string
        return $html;
    }

    /**
     * @method Interpreter removeStyle
     * @return void
     * 
     * This method removes styles from interpolateContent, would be added at the end of interpolation
     */
    private function removeStyle() : void
    {
        // @var array $styles
        $styles = [];

        // find all styles
        if (preg_match_all("/(<style)([\s\S]*?)(<\/style>)/m", $this->interpolateContent, $matches)) :
        
            foreach ($matches[0] as $style) :
            
                // @var string $hash
                $hash = md5($style);

                // update $styles
                $styles[$hash] = $style;

                // update interpolate content
                $this->interpolateContent = str_replace($style, "($hash)", $this->interpolateContent);

            endforeach;

            // update global styles
            $this->styles = array_merge($this->styles, $styles);

        endif;
    }

    /**
     * @method Interpreter addStyle
     * @return void
     * 
     * This method includes styles that was removed during interpolation. see method removeStyle()
     */
    private function addStyle() : void
    {
        if (count($this->styles) > 0) :
        
            foreach ($this->styles as $hash => $style) :
            
                // update interpolateContent with style
                $this->interpolateContent = str_replace("($hash)", $style, $this->interpolateContent);

            endforeach;

        endif;
    }

    /**
     * @method Interpreter loadReplaces
     * @return void
     */
    private function loadReplaces() : void 
    {
        if (isset(self::$externalConfiguration['replace'])) :

            // @var array $replaces
            $replaces = self::$externalConfiguration['replace'];

            // add replaces
            foreach ($replaces as $find => $replace) :

                // find and replace
                $this->interpolateContent = str_replace($find, $replace, $this->interpolateContent);

            endforeach;

        endif;
    }

    /**
     * @method Interpreter removeScript
     * @param string &$data
     * @param array &$hasScript
     * @return void
     * 
     * This method removes script tags from the DOM.
     */
    public function removeScript(string &$data, array &$hasScript) : void 
    {
        // @var string $script
        $script = strstr($data, "<script");

        // continue if we have script tags
        if ($script !== false) :
        
            // find all
            preg_match_all('/(<script)\s*(.*?)>/', $script, $scripts);

            if (count($scripts[0]) > 0) :
            
                foreach ($scripts[0] as $tag)
                {
                    // get script block
                    $block = $this->getblock($script, $tag, 'script');

                    // clean block
                    $cleanBlock = trim(strip_tags($block));

                    // continue if we have contents
                    if (strlen($cleanBlock) > 3) :
                    
                        $hash = md5($block);

                        // update has script array
                        $hasScript[$hash] = $block;

                        // update data 
                        $data = str_replace($block, $hash, $data);

                    endif;
                }

            endif;

        endif;
    }

    /**
     * @method Interpreter interpolateExternal
     * @param string $data
     * @param string $interpolated (reference)
     * @return string
     *
     * This method interpolates a complete DOM.
     * @throws ReflectionException
     */
    public function interpolateExternal(string $data, string &$interpolated = '') : string 
    {
        // @var array $hasScript
        static $hasScript;

        if (strlen($data) > 2) :

            // @var bool $continue
            $continue = true;

            // update $hasScript array
            if ($hasScript == null) $hasScript = [];

            // decode data
            $data = html_entity_decode($data, ENT_QUOTES, 'UTF-8');

            // remove scripts
            $this->removeScript($data, $hasScript);

            // update interpolate content
            $this->interpolateContent = $data;

            // remove style
            $this->removeStyle();

            // find binds
            preg_match_all("/({[^\n]*?)}/m", $this->interpolateContent, $binds);

            // replace binds
            $this->replaceBinds($binds);

            // load attributes
            $this->loadAttributes($this->interpolateContent);

            // include excluded script tags
            if (count($hasScript) > 0) :
            
                foreach($hasScript as $hash => $block) :
                
                    $this->interpolateContent = str_replace($hash, $block, $this->interpolateContent);

                endforeach;

            endif;

            // add style tag
            $this->addStyle();

            // load replaces
            $this->loadReplaces();

            // update interpolated
            $interpolated = $this->interpolateContent;

        endif;

        // return string
        return $interpolated;
    }

    /**
     * @method Interpreter yieldTemplate
     * @param string $template (reference)
     * @return void
     * 
     * This method would yield a template. 
     */
    public static function yieldTemplate(string &$template) : void
    {
        // see method Interpreter::yieldCallback
        if (count(self::$yieldUsed) > 0) :

            // @var array tagLines
            $tagLines = [];

            // get all tags and masks
            foreach (self::$yieldUsed as $tagname => $mask) :

                // clean tag name
                $tagname = preg_replace('/[^a-zA-Z0-9\-\_\@]/', '', $tagname);

                // remove time
                $tagnameClean = substr($tagname, 0, strpos($tagname, '@time'));

                // get before yield
                $beforeYield = \Happy\Directives::runDirective(false, "before-yield", $tagnameClean);

                // @var string $content
                $content = '';

                // get child comment
                $child = \Happy\Directives::runDirective(false, "yield-child");

                // Look for mask
                $maskQuote = preg_quote($mask);

                if ( preg_match_all("/($maskQuote)/", $template, $maskAll)) :

                    // @var string $tagname
                    $tagnameClean = preg_quote($tagnameClean);

                    // @var string $tagInner
                    $tagInner = '';

                    // @var string $tagLine
                    $tagLine = '';

                    if (preg_match_all("/([<]($tagnameClean)[>])([\s\S]+?)[<][\/]($tagnameClean)[>]/", $template, $match)) :
                    
                        $tagLine = $match[0][0];
                        $tagInner = $match[3][0];

                    endif;

                    // add tag line
                    $tagLines[] = $tagLine;

                    // load all mask
                    foreach ($maskAll[0] as $mask) :

                        // try to get the content before start
                        if (strpos($template, $beforeYield) !== false) :

                            // get the content
                            $content = substr($template, strpos($template, $beforeYield));

                            // get where it ends
                            $content = substr($content, 0, strpos($content, $mask));

                            // remove content
                            $template = str_replace($content, '', $template);
                            
                            // remove comment
                            $content = str_replace($beforeYield, '', $content);

                        endif;

                        // create a copy
                        $tagInnerCopy = $tagInner;

                        // look for child
                        $tagInnerCopy = str_replace($child, $content, $tagInnerCopy);

                        // update template
                        $template = str_replace($mask, $tagInnerCopy, $template);
                        

                        // var_dump($tagLine);
                        

                    endforeach;

                endif;

            endforeach;

            // remove all tag lines
            foreach ($tagLines as $tagLine) $template = str_replace($tagLine, '', $template);
            
        endif;
    }

    /**
     * @method Interpreter loadNamespaces
     * @param string $content
     * @return string
     */
    public static function loadNamespaces(string $content) : string 
    {
        if (isset(self::$externalConfiguration['namespaces'])) :

            // @var array $namespaces
            $namespaces = self::$externalConfiguration['namespaces'];

            // find namespaces
            $namespaceContent = '<?php' . "\n";

            foreach ($namespaces as $namespace) :

                $namespaceContent .= 'use ' . $namespace . ';' . "\n"; 

            endforeach;

            // append closing tag
            $namespaceContent .= '?>';

            // prepend content
            $content = $namespaceContent . $content;

        endif;

        // return string
        return $content;
    }

    /**
     * @method Interpreter interpolateText
     * @param string $data (reference)
     * @param Interpreter $templateEngine (null by default)
     * @return void
     * 
     * This method would interpolate a text
     */
    public static function interpolateText(string &$data, $templateEngine=null) : void
    {
        static $th;

        // create instance
        if ($th == '') $th = new self;

        // update interpolateContent
        $th->interpolateContent = $data;

        // update @var templateEngine
        if (!is_null($templateEngine)) $templateEngine = $th;

        // find all binds
        preg_match_all('/({{[\s\S]*?)}}/m', $data, $matches);

        // replace binds
        $th->replaceBinds($matches);

        // update data reference
        $data = $th->interpolateContent;
    }

    /**
     * @method Interpreter yieldCallback
     * @param string $tagName
     * @return void
     * 
     * This method would be called when we need to mask a tag that would later be yielded.
     */
    public static function yieldCallback(string $tagName) : void
    {
        // update tag name
        $tagName .= '@time='.(time() + mt_rand(1, 10000));

        // @var string $mask
        $mask = '<!--#(yield-'.$tagName.')-->';

        // update yield used
        self::$yieldUsed[$tagName] = $mask;

        // print mask to screen
        echo $mask;

        // register subscriber
        Engine::registerSubscriber('yield', function(){
            
            // get output
            $output = ob_get_contents();
            ob_clean();

            // start buffer
            ob_start();

            // yeild template
            Interpreter::yieldTemplate($output);

            // print output
            echo $output;
        }); 
    }   
}