<?php
namespace Lightroom\Templates\Happy\Web;

/**
 * @package Interpreter Attributes
 * @author Amadi Ifeanyi <amadiify.com>
 */
class InterpreterAttributes extends Interpreter
{
    /**
     * @var string interpolateExternal
     */
    public $interpolateExternal = '';

    /**
     * @var array $attributes
     */
    public static $attributes = [];

    /**
     * @method InterpreterAttributes loadExternalEngine
     * @return void
     */
    public function loadExternalEngine() : void 
    {
        foreach (parent::$engines as $engine) :

            // set interpreter
            call_user_func([$engine, 'setInterpreter'], $this);

            // load engine
            $this->interpolateExternal = call_user_func([$engine, 'initEngine'], $this->interpolateExternal);

        endforeach;
    }

    /**
     * @method InterpreterAttributes loadIf
     * @return void
     */
    public function loadIf() : void 
    {
        // php-if attribute
        preg_match_all("/<\s*\w.*(php-if=)\s*\"?\s*([\w\s%#\/\.;:_-]?.*)\s*\"(\s*>|(\s*\S*?>))/", $this->interpolateExternal, $matches);

        if (count($matches) > 0 && count($matches[0]) > 0) :
        
            foreach($matches[0] as $tag) :
            
                // get tag name
                preg_match('/[<]([\S]+)/', $tag, $tagName);
                $tagName = $tagName[1];
                $attribute = 'php-if';
                $attr = preg_quote($attribute, '/');

                // get quote
                preg_match("/($attr)\s*=\s*(['|\"])/",$tag, $getQuote);
                $getQuote = $getQuote[2];

                // get argument for attribute
                preg_match("/($attr)\s*=\s*([$getQuote])([\s\S]*?[$getQuote])/", $tag, $getAttribute);
                $getQuote = null;

                // @var string $attributeDeclaration
                $attributeDeclaration = $getAttribute[0];

                // @var string $getAttribute
                $getAttribute = preg_replace('/[\'|"]$/','',(isset($getAttribute[3]) ? $getAttribute[3] : ''));

                $ifLine = '<?php'."\n";
                $ifLine .= 'if('.$getAttribute.'){?>'."\n";

                // get before
                $begin = strstr($this->interpolateExternal, $tag);
                $before = $this->getblock($begin, $tag, $tagName);

                // @var string $block
                $block = preg_replace('/([<])([\S]+)\s{1,}[>]/', '<$2>', substr_replace($before, '', strpos($before, $attributeDeclaration), strlen($attributeDeclaration)));

                $ifLine .= $block;
                $ifLine .= "\n<?php }\n";
                $ifLine .= '?>';

                $this->interpolateExternal = str_replace($before, $ifLine, $this->interpolateExternal);

            endforeach;

        endif;
    }

    /**
     * @method InterpreterAttributes loadForeach
     * @return void
     */
    public function loadForeach() : void 
    {
        // php-for attribute
        preg_match_all("/<\s*\w.*(php-for=)\s*\"?\s*([\w\s%#\/\.;:_-]?.*)\s*\"(\s*>|(\s*\S*?>))/", $this->interpolateExternal, $matches);

        if (count($matches) > 0 && count($matches[0]) > 0) :
        
            foreach($matches[0] as $tag) :
            
                // get tag name
                preg_match('/[<]([\S]+)/', $tag, $tagName);
                $tagName = $tagName[1];
                $attribute = 'php-for';
                $attr = preg_quote($attribute, '/');

                // get quote
                preg_match("/($attr)\s*=\s*(['|\"])/",$tag, $getQuote);
                $getQuote = $getQuote[2];

                // get argument for attribute
                preg_match("/($attr)\s*=\s*([$getQuote])([\s\S]*?[$getQuote])/", $tag, $getAttribute);

                // @var string $attributeDecleration
                $attributeDecleration = $getAttribute[0];

                // @var string $getAttribute
                $getAttribute = preg_replace('/[\'|"]$/','',(isset($getAttribute[3]) ? $getAttribute[3] : ''));

                // get before
                $begin = strstr($this->interpolateExternal, $tag);
                $before = $this->getblock($begin, $tag, $tagName);

                // @var string $block
                $block = preg_replace('/([<])([\S]+)\s{1,}[>]/', '<$2>', substr_replace($before, '', strpos($before, $attributeDecleration), strlen($attributeDecleration)));

                // manage limit
                $limit = null;
                if (preg_match("/(limit)\s*=\s*(['|\"])([\s\S]*?)(['|\"])/", $tag, $match)) $limit = $match[3]; $block = str_replace((isset($match[0]) ? $match[0] : ''), '', $block);

                $bind = $attribute;
                $attribute = $getAttribute;

                if (strpos($attribute, ' in ') > 2) :
                
                    $statement = explode(' in ', $attribute);

                    if (count($statement) == 2) :
                    
                        $left = $statement[0];
                        $right = $statement[1];

                        // @var string $value
                        $value = '';

                        // @var string $key
                        $key = '';

                        // @var array $keyValue
                        $keyValue = explode(',', $left);

                        foreach($keyValue as $index => $key) $keyValue[$index] = trim($key);

                        // update value
                        $value = $keyValue[0];

                        if (count($keyValue) == 2) :
                        
                            $key = $keyValue[0];
                            $value = $keyValue[1];

                        endif;

                        $forLine = '<?php'."\n";
                        $forLine .= 'if (is_array('.$right.') || is_object('.$right.')){'."\n";
                        $forLine .= '$foreachIndex = 0;' . "\n";
                        $forLine .= "foreach ($right ";

                        if ($key !== null) :
                        
                            $forLine .= "as $key => $value){\n";
                        
                        else:
                        
                            $forLine .= "as $value){\n";

                        endif;

                        $forLine .= '$foreachIndex++;';
                        $forLine .= "?>\n";
                        $forLine .= $block . "\n";

                        if ($limit != null) $forLine .= '<?php if ($foreachIndex == '.intval($limit).'){ break; } ?>';
         
                        $forLine .= "<?php }\n}?>";

                        $this->interpolateExternal = str_replace($before, $forLine, $this->interpolateExternal);

                    endif;

                endif;

            endforeach;

        endif;
    }

    /**
     * @method InterpreterAttributes loadWhile
     * @return void 
     */
    public function loadWhile() : void 
    {
        // php-while attribute
        preg_match_all("/<\s*\w.*(php-while=)\s*\"?\s*([\w\s%#\/\.;:_-]?.*)\s*\"(\s*>|(\s*\S*?>))/", $this->interpolateExternal, $matches);

        if (count($matches) > 0 && count($matches[0]) > 0) :
        
            foreach($matches[0] as $tag) :
            
                // get tag name
                preg_match('/[<]([\S]+)/', $tag, $tagName);
                $tagName = $tagName[1];
                $attribute = 'php-while';
                $attr = preg_quote($attribute, '/');

                // get quote
                preg_match("/($attr)\s*=\s*(['|\"])/",$tag, $getQuote);
                $getQuote = $getQuote[2];

                // get argument for attribute
                preg_match("/($attr)\s*=\s*([$getQuote])([\s\S]*?[$getQuote])/", $tag, $getAttribute);

                // @var string $attributeDeclaration
                $attributeDecleration = $getAttribute[0];

                // @var string $getAttribute
                $getAttribute = preg_replace('/[\'|"]$/','', (isset($getAttribute[3]) ? $getAttribute[3] : ''));

                // get before
                $begin = strstr($this->interpolateExternal, $tag);
                $before = $this->getblock($begin, $tag, $tagName);

                // @var string $block
                $block = preg_replace('/([<])([\S]+)\s{1,}[>]/', '<$2>', substr_replace($before, '', strpos($before, $attributeDecleration), strlen($attributeDecleration)));

                $bind = $attribute;
                $attribute = $getAttribute;

                // manage limit
                $limit = null;
                if (preg_match("/(limit)\s*=\s*(['|\"])([\s\S]*?)(['|\"])/", $tag, $match)) $limit = $match[3]; $block = str_replace((isset($match[0]) ? $match[0] : ''), '', $block);

                // @var array $statement
                $statement = explode(' is ', $attribute);
 
                if (count($statement) > 0) :
                
                    $left = trim($statement[0]);
                    $right = isset($statement[1]) ? $statement[1] : '';

                    // @var string $whileStatement
                    $whileStatement = (strlen($right) > 1 && strlen($left) > 0) ? $left.' = '.$right : $left;

                    $whileLine  = '<?php'."\n";
                    $whileLine .= '$whileIndex = 0;' . "\n";
                    $whileLine .= 'while ('.$whileStatement.'){ ?>'."\n";
                    $whileLine .= $block . "\n";
                    if ($limit != null) $whileLine .= '<?php '."\n".' if ($whileIndex == '.intval($limit).'){ break; } ?>';
                    $whileLine .= "\n<?php \$whileIndex++; }?>";

                    $this->interpolateExternal = str_replace($before, $whileLine, $this->interpolateExternal);

                endif;

            endforeach;

        endif;
    }

    /**
     * @method InterpreterAttributes loadIfBinds
     * @return void
     */
    public function loadIfBinds() : void 
    {
        // @var array $binds
        $binds = [
            'php-if::id' => 'id',
            'php-if::class' => 'class',
            'php-if::for' => 'for',
            'php-if::name' => 'name',
            'php-if::type' => 'type',
            'php-if::placeholder' => 'placeholder',
            'php-if::src' => 'src',
            'php-if::href' => 'href',
            'php-if::value' => 'value',
            'php-if::action' => 'action',
            'php-if::method' => 'method',
            'php-if::style' => 'style'
        ];

        // find all binds, replace with attribute
        foreach($binds as $bind => $attribute) :
        
            // @var string $bindAttribute
            $bindAttribute = preg_quote($bind);

            // find all bind attributes
            preg_match_all("/<\s*\w.*($bindAttribute=)\s*\"?\s*([\w\s%#\/\.;:_-]?.*)\s*\"(\s*>|(\s*\S*?>))/", $this->interpolateExternal, $matches);

            // @var array $alltags
            $alltags = [];

            if (count($matches[0]) > 0) :
            
                foreach ($matches[0] as $tag) :
                
                    // @var string $tag
                    $tag = trim($tag);

                    if (preg_match("/[>]$/", $tag)) :
                    
                        // append tag
                        $alltags[] = $tag;
                    
                    else:
                    
                        $tagQuote = preg_quote($tag, '/');

                        // find tag
                        preg_match("/($tagQuote)\s*\"?\s*([\w\s%#\/\.;:_-]*)\s*\"?.*>/", $this->interpolateExternal, $tagMatches);

                        // append tag
                        if (isset($tagMatches[0])) $alltags[] = $tagMatches[0];

                    endif;

                endforeach;

            endif;

            if (count($alltags) > 0) :
            
                foreach($alltags as $tag) :
                
                    // get tag name
                    preg_match('/[<]([\S]+)/', $tag, $tagName);
                    $tagName = $tagName[1];
                    $bindQuote = preg_quote($bind, '/');

                    // get quote
                    preg_match("/($bindQuote)\s*=\s*(['|\"])/",$tag, $getQuote);
                    $getQuote = $getQuote[2];

                    // get argument for attribute
                    preg_match("/($bindQuote)\s*=\s*([$getQuote])([\s\S]*?[$getQuote])/", $tag, $getAttribute);

                    // @var string $attributeDecleration
                    $attributeDecleration = $getAttribute[0];

                    // @var string $getAttribute
                    $getAttribute = preg_replace('/[\'|"]$/','', (isset($getAttribute[3]) ? $getAttribute[3] : ''));

                    $this->interpolateExternal = str_replace($attributeDecleration, ' '.$attribute.'="<?=('.$getAttribute.')?>"', $this->interpolateExternal);
                
                endforeach;

            endif;
        
        endforeach;
    }

    /**
     * @method InterpreterAttributes loadBackgroundImage
     * @return void
     */
    public function loadBackgroundImage() : void 
    {
        preg_match_all('/[<]([\S]+)([^>]+)?(\$background-image)\s{0,}[=][\'|"]([^\'|"]+)[\'|"]([^>]+|)[>|]/', $this->interpolateExternal, $matches);

        if (count($matches[0]) > 0) :
        
            foreach ($matches[0] as $index => $data) :
                
                // @var string $replace
                $replace = $data;

                // @var string $image
                $image = '"'.preg_replace("/[{]|[}]/",'', $matches[4][$index]).'"';

                // @var string $imgStyle  
                $imgStyle = "background-image:url('<?=assets_image($image)?>')";

                // find attributes
                preg_match('/(\$background-image)\s{0,}[=][\'|"]([^\'|"]+)[\'|"]/', $data, $attribute);
                $attr = $attribute[0];

                preg_match('/(style)\s{0,}[=][\'|"]([^\'|"]+)[\'|"]/', $data, $style);

                if (count($style) > 0) :
                
                    // @var string $styles
                    $styles =  rtrim(trim(end($style)), ';') . '; '.$imgStyle.';';

                    // update data
                    $data = str_replace($style[0], 'style="'.$styles.'"', $data);

                else:
                
                    // update data
                    $data = str_replace($attr, 'style="'.$imgStyle.';"', $data);

                endif;

                // remove attribute
                $data = str_replace($attr,'',$data);

                // update interpolateExternal
                $this->interpolateExternal = str_replace($replace, $data, $this->interpolateExternal);
            
            endforeach;

        endif;
    }

    /**
     * @method InterpreterAttributes loadBackgroundImagePreloader
     * @return void
     */
    public function loadBackgroundImagePreloader() : void 
    {
        preg_match_all('/[<]([\S]+)([^>]+)?(\$background-async)\s{0,}[=][\'|"]([^\'|"]+)[\'|"]([^>]+|)[>|]/', $this->interpolateExternal, $matches);

        if (count($matches[0]) > 0) :
        
            foreach ($matches[0] as $index => $data) :
                
                // @var string $replace
                $replace = $data;

                // @var string $image
                $image = '"'.preg_replace("/[{]|[}]/",'', $matches[4][$index]).'"';

                // @var string $imgStyle  
                $imgStyle = "";

                // find attributes
                preg_match('/(\$background-async)\s{0,}[=][\'|"]([^\'|"]+)[\'|"]/', $data, $attribute);
                $attr = $attribute[0];

                preg_match('/(style)\s{0,}[=][\'|"]([^\'|"]+)[\'|"]/', $data, $style);

                if (count($style) > 0) :
                
                    // @var string $styles
                    $styles =  rtrim(trim(end($style)), ';') . '; '.$imgStyle;

                    // update data
                    $data = str_replace($style[0], 'style="'.$styles.'" data-async-image="<?=assets_image('.$image.')?>"', $data);

                else:
                
                    // update data
                    $data = str_replace($attr, 'style="'.$imgStyle.'" data-async-image="<?=assets_image('.$image.')?>"', $data);

                endif;
                

                // remove attribute
                $data = str_replace($attr,'',$data);

                // update interpolateExternal
                $this->interpolateExternal = str_replace($replace, $data, $this->interpolateExternal);
            
            endforeach;

        endif;
    }

    /**
     * @method InterpreterAttributes loadScript
     * @return void
     */
    public function loadScript() : void 
    {
        preg_match_all('/[<](script)(.*)(\$src)\s{0,}[=][\'|"]([^\'|"]+)[\'|"]/', $this->interpolateExternal, $matches);

        // call load helper
        $this->loadHelper($matches, '$src', 'src', 'assets_js');
    }

    /**
     * @method InterpreterAttributes loadPoster
     * @return void
     */
    public function loadPoster() : void 
    {
        preg_match_all('/[<](video|audio|source)(.*)(\$poster)\s{0,}[=][\'|"]([^\'|"]+)[\'|"]/', $this->interpolateExternal, $posters);

        // call load helper
        $this->loadHelper($posters, '$poster', 'poster', 'assets_image');
    }

    /**
     * @method InterpreterAttributes loadMedia
     * @return void
     */
    public function loadMedia() : void 
    {
        preg_match_all('/[<](video|audio|source)(.*)(\$media)\s{0,}[=][\'|"]([^\'|"]+)[\'|"]/', $this->interpolateExternal, $matches);

        // call load helper
        $this->loadHelper($matches, '$media', 'src', 'assets_media');
    }

    /**
     * @method InterpreterAttributes loadCss
     * @return void
     */
    public function loadCss() : void 
    {
        preg_match_all('/[<](link)(.*)(\$href)\s{0,}[=][\'|"]([^\'|"]+)[\'|"]/', $this->interpolateExternal, $matches);

        // call load helper
        $this->loadHelper($matches, '$href', 'href', 'assets_css');
    }

    /**
     * @method InterpreterAttributes loadImage
     * @return void
     */
    public function loadImage() : void 
    {
        preg_match_all('/[<](img)(.*)(\$src)\s{0,}[=][\'|"]([^\'|"]+)[\'|"]/', $this->interpolateExternal, $matches);

        // call load helper
        $this->loadHelper($matches, '$src', 'src', 'assets_image');
    }

    /**
     * @method InterpreterAttributes loadImagePreloader
     * @return void
     */
    public function loadImagePreloader() : void 
    {
        preg_match_all('/[<](img)(.*)(\$async)\s{0,}[=][\'|"]([^\'|"]+)[\'|"]/', $this->interpolateExternal, $matches);

        // call load helper
        $this->loadHelper($matches, '$async', 'data-async-src', 'assets_image');
    }

    /**
     * @method InterpreterAttributes loadLink
     * @return void
     */
    public function loadLink() : void 
    {
        preg_match_all('/[<](a)(.*)(\$href|\$shref)\s{0,}[=][\'|"]([^\'|"]+)[\'|"]/', $this->interpolateExternal, $matches);

        foreach ($matches[0] as $i => $match)
        {
            // @var bool $href
            $href = true;

            // @var bool $hrefSecure
            $hrefSecure = false;
            
            // target
            $target = '$href';

            if (strstr($match, '$shref') == true) :
            
                $target = '$shref'; 
                $hrefSecure = true;

            endif;

            // @var string $replace
            $replace = $match;

            // @var string $find
            $find = substr($match, strpos($match, $target));

            // @var string $value
            $value = '"'.preg_replace("/[{]|[}]/", '', preg_replace('/[\'|"]/', '', substr($find, strpos($find, '=')+1))).'"';

            // @var string $href
            $href = 'href="<?=web_url('.$value.')?>"';

            if ($hrefSecure) :
            
                $href = 'href="<?=web_secure_url('.$value.')?>"';

            endif;

            $this->interpolateExternal = str_replace($replace, str_replace($find, $href, $match), $this->interpolateExternal);
        }
    }

    /**
     * @method InterpreterAttributes loadHelper
     * @param array $matches
     * @param string $find
     * @param string $target
     * @param string $function
     * @return void
     */
    private function loadHelper(array $matches, string $find, string $target, string $function) : void
    {
        foreach ($matches[0] as $match)
        {
            // @var string $attribute
            $attribute = substr($match, strpos($match, $find));

            // @var string $replace
            $replace = $match;

            // @var string $other
            $other = null;

            // @var string $value
            $value = '"'.preg_replace("/[{]|[}]/", '', preg_replace('/[\'|"]/', '', substr($attribute, strpos($attribute, '=')+1))).'"';

            // update other
            $other = $target . '="<?=' . $function . '('.$value.')?>"';

            // update interpolateExternal
            $this->interpolateExternal = str_replace($replace, str_replace($attribute, $other, $match), $this->interpolateExternal);
        }
    }
}