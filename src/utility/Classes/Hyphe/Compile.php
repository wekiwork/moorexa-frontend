<?php
/** @noinspection All */
namespace Hyphe;

use Masterminds\HTML5;
use function Hyphe\Functions\{save_json, read_json};

// include functions
include_once 'Functions.php';

class Compile
{
    // root directory
    public static $rootDir = PATH_TO_DIRECTIVES;

    // cache directory
    private static $cacheDir = __DIR__ .'/Caches/';

    // self closing tags
    private static $selfClosing = [
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

    // compile file.
    public static function CompileFile(string $filename, $namespace = '', $directive = null)
    {

        // check directive
        $directive = is_null($directive) ? self::$rootDir : $directive;


        if (file_exists($filename))
        {
            // read file content
            return self::runCompile($filename, $namespace);
        }
        else {
            // check 
            if (is_dir(self::$rootDir))
            {
                // scan for file
                $path = self::deepScan($directive, $filename);

                return self::runCompile($path, $namespace);
            }
        }

        return null;
    }

    // run compile and return path
    private static function runCompile(string $path, $namespace)
    {
        // path was returned ?
        if (!is_null($path))
        {
            $filename = basename($path);

            $cachename = md5($path) . '.php';

            if (file_exists($path))
            {
                // yes we read its content.
                $content = file_get_contents($path);

                $default = $content;

                $continue = true;
                $json = read_json(__DIR__ . '/hyphe.paths.json', true);
                
                if (file_exists(self::$cacheDir . $cachename))
                {
                    $_content = file_get_contents(self::$cacheDir . $cachename);

                    $start = strstr($_content, 'public static function ___cacheData()');

                    preg_match('/(return)\s{1,}["](.*?)["]/', $start, $return);

                    if (count($return) > 0)
                    {
                        $cached = $return[2];

                        if ($cached == md5($default))
                        {
                            $continue = false;
                        }
                    }

                    // clean up
                    $_content = null;
                    $return = null;
                }
                
                if ($continue)
                {
                    $content = '<!doctype html><html><body>'.$content.'</body></html>';
        
                    // read dom
                    $html = new HTML5();
                    $dom = $html->loadHTML($content);

                    $replaces = [];
                    $engine = new Engine();

                    $cachesize = md5($default);

                    foreach ($dom->getElementsByTagName('hy') as $hy)
                    {
                        if ($hy->hasAttribute('directive'))
                        {
                            $class = $hy->getAttribute('directive');
                            // inner content
                            $body = self::innerHTML($hy);

                            $classMap = [];
                            $classMap[] = '<?php';
                            if ($namespace != '')
                            {
                                $classMap[] = 'namespace '.$namespace.';';
                            }
                            else
                            {
                                $namespace = rtrim($path, $filename);
                                $namespace = preg_replace('/^(\.\/)/','',$namespace);
                                $namespace = rtrim($namespace, '/');
                                $namespace = str_ireplace('/static/', '/', $namespace);
                                $namespace = str_replace(HOME, '', $namespace);
                                $namespace = preg_replace('/[\/]{2,}/', '/', $namespace);
                                $namespace = str_replace('/', '\\', $namespace);
                                $classMap[] = 'namespace '.$namespace.';';
                            }
                            
                            $classMap[] = 'class '.ucfirst($class). ' extends \Hyphe\Engine {';
                            $classMap[] = html_entity_decode($body);
                            $classMap[] = 'public static function ___cacheData()';
                            $classMap[] = '{';
                            $classMap[] = '  return "'.$cachesize.'";';
                            $classMap[] = '}';
                            $classMap[] = '}';

                            $replaces[] = [
                                'replace' => $dom->saveHTML($hy),
                                'with' => implode("\n\t", $classMap)
                            ];
                        }
                        else
                        {
                            $out = $dom->saveHTML($hy);
                            $inner = self::innerHTML($hy);

                            if ($hy->hasAttribute('func'))
                            {
                                $funcName = $hy->getAttribute('func');
                                $access = $hy->hasAttribute('access') ? $hy->getAttribute('access') : 'public';
                                $args = $hy->hasAttribute('args') ? $hy->getAttribute('args') : '';

                                $func = [];
                                $func[] = $access .' function '. $funcName . '('.$args.')';
                                $func[] = '{';
                                $func[] = html_entity_decode($inner);
                                $func[] = '}'; 

                                $replaces[] = [
                                    'replace' => html_entity_decode($out),
                                    'with' => implode("\n\t", $func)
                                ];
                            }
                            else
                            {
                                if ($hy->hasAttribute('lang'))
                                {
                                    $lang = $hy->getAttribute('lang');

                                    switch (strtolower($lang))
                                    {
                                        case 'html':

                                            // interpolate props and this
                                            $inner = preg_replace('/(props)[.]([a-zA-Z_]+)/', '$this->props->$2', $inner);
                                            $inner = preg_replace('/(this)[.]([a-zA-Z_]+)/', '$this->$2', $inner);

                                            $inner = html_entity_decode($inner);

                                            $engine->interpolateExternal($inner, $data);
                                            $return = [];
                                            $return[] = '$assets = $this->loadAssets();';
                                            $return[] = '?>';
                                            $return[] = $data;
                                            $return[] = '<?php';

                                            // add replace
                                            $replaces[] = [
                                                'replace' => html_entity_decode($out),
                                                'with' => implode("\n\t", $return)
                                            ];
                                        break;
                                    }
                                }    
                            }
                        }
                    }

                    if (isset($replaces[0]))
                    {
                        $default = $replaces[0]['replace'];

                        $count = count($replaces);

                        $default = preg_replace('/(\S+)(=\s*)[\'](.*?)[\']/', '$1$2"$3"', $default);

                        $default = self::removeClosingTags($default);

                        for ($x = 0; $x != $count; $x++)
                        {
                            $replace = self::removeClosingTags($replaces[$x]['replace']);
                            $with = self::removeClosingTags($replaces[$x]['with']);

                            if ($x == 0)
                            {
                                $default = $with;
                            }

                            $default = str_replace($replace, $with, $default);
                        }

                        // interpolate props and this
                        $default = preg_replace('/(props)[.]([a-zA-Z_]+)/', '$this->props->$2', $default);
                        $default = preg_replace('/(this)[.]([a-zA-Z_]+)/', '$this->$2', $default);


                        if (!is_dir(self::$cacheDir))
                        {
                            mkdir(self::$cacheDir);
                        }

                        // save cache file and return path
                        file_put_contents(self::$cacheDir . $cachename, $default);

                        // push to json
                        $json[$cachename] = $namespace;
                        save_json(__DIR__ . '/hyphe.paths.json', $json);
                    }
                }
            }
            
            return self::$cacheDir . $cachename;
        }

        return null;
    }

    // remove ending tag for self closing tags
    public static function removeClosingTags(string $text)
    {
        foreach (self::$selfClosing as $tag)
        {
            if (strpos($text, '</'.$tag.'>') !== false)
            {
                $text = str_replace('</'.$tag.'>', '', $text);
            }
        }

        return html_entity_decode($text);
    }

    // parse doc 
    public static function ParseDoc(&$doc)
    {
        $domcopy = $doc;
        $hasPhp = false;

        // php tags
        $phpTags = [];

        if (strpos($domcopy, '<?=') !== false)
        {
            $hasPhp = true;

            preg_match_all('/(<\?=)([\s\S]+?)(\?>)/', $domcopy, $echo);

            foreach ($echo[0] as $phpSelfIndex => $phpSelf) :

                // create replacement
                $replace = '<?=' . $echo[2][$phpSelfIndex] . '?>';

                // replace now
                $domcopy = str_replace($phpSelf, $replace, $domcopy);

            endforeach;
        }

        // find php in doc
        preg_match_all('/(<\?=|<\?php)([\s\S]+?)(\?>)/', $domcopy, $echo);

        // remove all
        if (count($echo[0]) > 0) :
            
            foreach ($echo[0] as $phpSelf) :    

                // hash line and save
                $hashLine = '(' . md5($phpSelf) . ')';

                // save
                $phpTags[$hashLine] = $phpSelf;

                // replace in dom
                $domcopy = str_replace($phpSelf, $hashLine, $domcopy);

            endforeach;

            // replace dom
            $doc = $domcopy;

        endif;

        // read dom
        $html = new HTML5();
        $dom = $html->loadHTML('<data-body>'.$domcopy.'</data-body>');

        preg_match_all('/(<hy(.*?>))+([\s\S]+?)(<\/hy>)/i', $domcopy, $matches);

        if (count($matches[0]) > 0)
        {
            $directory = self::$rootDir;

            foreach($matches[3] as $index => $innerHTML)
            {
                $outerHTML = $matches[0][$index];

                // read attributes 
                $attributeString = $matches[2][$index];
                // check for attribute directory
                if (stripos($attributeString, 'directory') !== false)
                {
                    $document = '<section id="has-directory" '.$attributeString . '</section>';
                    // get do
                    $load = $html->loadHTML($document);
                    $element = $load->getElementById('has-directory');
                    $directory = $element->getAttribute('directory');
                }

                $hash = md5($innerHTML);
                $hash = preg_replace('/[0-9]/','',$hash);
                $var = '$'.$hash;

                $innerHTML = html_entity_decode($innerHTML);

                preg_match_all('/([{]|<\?php)(.*?)[}]/', $innerHTML, $phpshorttags);

                $variables = [];

                // remove php 
                preg_match_all('/(<\?php)(.*?)(\?>)/', $innerHTML, $phpTag);

                foreach ($phpTag[0] as $phpTagLineIndex => $phpTagLine) :

                    $variables[] = $phpTag[2][$phpTagLineIndex];
                    $innerHTML = str_replace($phpTagLine, '', $innerHTML);

                endforeach;
                
                if (count($phpshorttags[0]) > 0)
                {
                    foreach ($phpshorttags[0] as $index => $shorttag)
                    {
                        $hash = md5($shorttag);
                        $hash = preg_replace('/[0-9]/','',$hash);

                        $rightVal = $phpshorttags[2][$index];

                        $rightVal = strlen($rightVal) == 0 ? "''" : $rightVal;

                        if (strpos($shorttag, '=') === false && strlen(trim($rightVal)) > 0)
                        {
                            $variables[] = '$'.$hash .' = ' .$rightVal.';';
                            $innerHTML = str_replace($shorttag, '$'.$hash, $innerHTML);
                        }
                        else {

                            if (preg_match('/(==|>=|<=|===|!=|!==)/', $rightVal) == false) :
                                $variables[] = trim($rightVal);
                                $innerHTML = str_replace($shorttag, '', $innerHTML);
                            endif;
                        }
                    }
                }

                $innerHTML = str_replace('<?=', '{', $innerHTML);
                $innerHTML = str_replace('?>', '}', $innerHTML);

                $content = '';
                if (count($variables) > 0)
                {
                    $content .= implode("\n\t", $variables);
                    $content .= "\n";
                }
                $content .= $var . '= <<<EOT'. "\n";
                $content .= $innerHTML . "\n";
                $content .= 'EOT;';

                $build = [];
                $build[] = '<?php';
                $build[] = $content;
                $build[] = 'echo \Hyphe\Engine::ParseTags('.$var.', "'.$directory.'");';
                $build[] = '?>';

                $build = implode("\n\t", $build);

                $outerHTML = html_entity_decode($outerHTML);

                if ($hasPhp)
                {
                    $outerHTML = str_replace('{', '<?=', $outerHTML);
                    $outerHTML = str_replace('}', '?>', $outerHTML);
                }

                $doc = str_ireplace($outerHTML, $build, $doc);
            }
        }

        // get dom copy
        $document = str_replace('<hy-', '<data-element data-hy-element=', $doc);
        $document = preg_replace('/<\/(hy-(.*?>))/', '</data-element>', $document);

        $load = $html->loadHTML($document);
        $elements = $load->getElementsByTagName('data-element');

        foreach ($elements as $element) :

            // get outer html
            $outerHTML = $html->saveHTML($element);

            // replace
            $replace = $outerHTML;

            // get element
            $element = $element->getAttribute('data-hy-element');

            // clean outer
            $newBody = str_replace('data-element data-hy-element="'.$element.'"', 'hy-' .$element, $outerHTML);
            $replace = str_replace('data-element data-hy-element="'.$element.'"', 'hy-' .$element, $outerHTML);

            // get closing
            $closing = strrpos($newBody, '</data-element>');

            if ($closing !== false) :

                $newBody = substr($newBody, 0, $closing) . ' </hy-' . $element . '>';
                
                $closing = strrpos($replace, '</data-element>');
                $replace = substr_replace($replace, '</hy-' . $element . '>', $closing);

            endif;

            // clean the new body
            $load = $html->loadHTML($newBody);
            $elements = $load->getElementsByTagName('data-element');

            foreach ($elements as $ele) :

                // get element
                $element = $ele->getAttribute('data-hy-element');

                // get outer html
                $outerHTML = $html->saveHTML($ele);

                // before
                $before = $outerHTML;

                // get closing
                $closing = strrpos($outerHTML, '</data-element>');

                // clean outer
                $cleanOuter = $outerHTML;

                if ($closing !== false) :

                    $cleanOuter = substr($outerHTML, 0, $closing) . '</hy-' . $element . '>';
                    $outerHTML = substr($outerHTML, 0, $closing) . '</hy-' . $element . '>';

                endif;

                // clean outer
                $newBody = str_replace($before, str_replace('data-element data-hy-element="'.$element.'"', 'hy-' . $element, $cleanOuter), $newBody);
                $replace = str_replace($before, str_replace('data-element data-hy-element="'.$element.'"', 'hy-' . $element, $outerHTML), $replace);

            endforeach;

            // load var
            foreach ($phpTags as $hashLine => $replacement) $newBody = str_replace($hashLine, $replacement, $newBody);

            // fix var
            $newBody = str_replace('<?==', '<?=', $newBody);

            if (strpos($newBody, '<?=') !== false) :

                preg_match_all('/(<\?=)([\s\S]+?)(\?>)/', $newBody, $echo);

                foreach ($echo[0] as $phpSelfIndex => $phpSelf) :

                    // create replacement
                    $replacement = '{' . $echo[2][$phpSelfIndex] . '}';

                    // replace now
                    $newBody = str_replace($phpSelf, $replacement, $newBody);

                endforeach;

            endif;
            

            preg_match_all('/([{]|<\?php)(.*?)[}]/', $newBody, $phpshorttags);

            $variables = [];

            // remove php 
            preg_match_all('/(<\?php)(.*?)(\?>)/', $newBody, $phpTag);

            foreach ($phpTag[0] as $phpTagLineIndex => $phpTagLine) :

                $variables[] = $phpTag[2][$phpTagLineIndex];
                $newBody = str_replace($phpTagLine, '', $newBody);

            endforeach;

            if (count($phpshorttags[0]) > 0)
            {
                foreach ($phpshorttags[0] as $index => $shorttag)
                {
                    $hash = md5($shorttag);
                    $hash = preg_replace('/[0-9]/','',$hash);

                    $rightVal = $phpshorttags[2][$index];
                    $rightVal = strlen($rightVal) == 0 ? "''" : $rightVal;

                    if (strpos($shorttag, '=') === false && strlen(trim($rightVal)) > 0)
                    {
                        $variables[] = '$'.$hash .' = ' .$rightVal.';';
                        $newBody = str_replace($shorttag, '$'.$hash, $newBody);
                    }
                    else {

                        if (preg_match('/(==|>=|<=|===|!=|!==)/', $rightVal) == false) :

                            $variables[] = trim($rightVal);
                            $newBody = str_replace($shorttag, '', $newBody);

                        endif;
                    }
                }
            }

            $base64 = base64_encode($newBody);

            $build = [];
            $build[] = '<?php';
            $build[] = count($variables) > 0 ? implode("\n\t", $variables) . "\n" : '';
            $build[] = 'echo \Hyphe\Engine::ParseBase64("'.$base64.'", get_defined_vars());';
            $build[] = '?>';

            $build = implode(" ", $build);

            $doc = str_replace($replace, $build, $doc);

        endforeach;

        // load var
        foreach ($phpTags as $hashLine => $replacement) $doc = str_replace($hashLine, $replacement, $doc);
    }

    // Make a deep scan for files
    public static function deepScan($dir, $file)
    {
        $getjson = file_get_contents(__DIR__ . '/hyphe.paths.json');
        $json = json_decode($getjson);

        $failed = [];
        
        if (is_object($json))
        {
            $json = (array) $json;
        }
        else
        {
            $json = [];
        }

        $_path = "";
        $updateJson = false;
        $updateFailed = false;

        if(is_array($file))
        {
            $key = $dir.':'.implode(":",$file);

            if (isset($json[$key]))
            {
                $_path =  $json[$key];
            }
            else
            {
                if (!isset($failed[$key]))
                {
                    $found = false;
                    foreach($file as $inx => $ff)
                    {
                        if ($found == false)
                        {
                            $_path = self::__fordeepscan($dir, $ff);
                            if ($_path !== "") {
                                $found = true; 
                                $json[$key] = $_path;
                                $updateJson = true;
                                break;
                            }

                        }
                    }

                    if (!$found)
                    {
                        $updateFailed = true;
                        $failed[$key] = [$dir, $file];
                    }
                }
                else
                {
                    $arr = $failed[$key];
                    $dir = $arr[0];
                    if (is_dir($dir))
                    {
                        foreach ($arr[1] as $i => $file)
                        {
                            $build = $dir . $file;
                            if (file_exists($build))
                            {
                                $_path = $build;
                                break;
                            }
                        }
                    }
                    $arr = null;
                    $dir = null;
                }

                $file = null;
            }
        }
        else
        {
            $key = $dir.':'.$file;

            if (isset($json[$key]))
            {
                $_path = $json[$key];
            }
            else
            {
                if (!isset($failed[$key]))
                {
                    $_path = self::__fordeepscan($dir, $file);
                    if ($_path !== '')
                    {
                        $json[$key] = $_path;
                        $updateJson = true;
                    }

                    if ($_path == '')
                    {
                        $updateFailed = true;
                        $failed[$key] = [$dir, $file];
                    }
                }
                else
                {
                    $arr = $failed[$key];
                    $dir = $arr[0];
                    $build = $dir . $arr[1];

                    if (file_exists($build))
                    {
                        $_path = $build;
                    }
                    $arr = null;
                    $dir = null;
                }
            }
        }

        if ($updateJson)
        {
            $json = json_encode($json, JSON_PRETTY_PRINT);
            file_put_contents(__DIR__ . '/hyphe.paths.json', $json);
        }

        $dir = null;

        return $_path;
    }

    // helper function for deepScan
    private static function __fordeepscan($dir, $file)
    {
        $path = "";
        $scan = glob($dir.'/*');
        $q = preg_quote($file, '\\');

        if (is_array($scan))
        {
            foreach ($scan as $d => $f)
            {
                if ($f != '.' && $f != '..')
                {
                    $f = preg_replace("/[\/]{1,}/", '/', $f);

                    if (!is_dir($f))
                    {
                        $base = basename($f);

                        if (($base == $file) && strrpos($f, $file) !== false)
                        {
                            $path = $f;
                        }

                        $base = null;
                    }

                    if ($path == "")
                    {
                        $path = self::__fordeepscan($f, $file);
                        if ($path !== ""){
                            if (strrpos($path, $file) !== false){
                                break;
                            }
                        }
                    }

                    $f = null;
                }
            }

            $scan = null;
        }

        return $path;
    }

    // read element inner html
    public static function innerHTML(\DOMElement $element)
    {
        $doc = $element->ownerDocument;

        $html = '';

        foreach ($element->childNodes as $node)
        {
            $html .= $doc->saveHTML($node);
        }

        return $html;
    }
}