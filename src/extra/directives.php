<?php

use Happy\Directives;
use Lightroom\Events\Listener;
use Lightroom\Packager\Moorexa\Helpers\Partials;
use Lightroom\Templates\Happy\Web\Interpreter;
use function Lightroom\Requests\Functions\{post, get};
use Lightroom\Packager\Moorexa\Helpers\URL;

/**
 * @method Directives register
 * 
 * This method registers a list of global directives
 */
Directives::register([

    // loading directives from a class
    'while'      => [ Directives::class, '_while' ],
    'endforeach' => [ Directives::class, '_endforeach' ],
    'foreach'    => [ Directives::class, '_foreach' ],
    'for'        => [ Directives::class, '_for' ],
    'if'         => [ Directives::class, '_if' ],
    'elseif'     => [ Directives::class, '_elseif' ],
    'else'       => [ Directives::class, '_else' ],
    'endif'      => [ Directives::class, '_endif' ],
    'endfor'     => [ Directives::class, '_endfor' ],
    'endwhile'   => [ Directives::class, '_endwhile' ],
    'mask'       => [ Directives::class, '_masking' ],
    'partial'    => [ Partials::class,   'loadPartial' ],

    // Using callback functions
    'setdefault' => function() :string
    {
        return '<!--Default-->';
    },

    /**
     *@method csrf token directive
     *@return string
     */

    'csrf' => function() : string
    {
        return \Lightroom\Common\Functions\csrf();
    },

    /**
     *@method layout method directive
     *@return string
     */

    'yield' => function(string $tagName) : string
    {
        // update comment
        return '<?php '.Interpreter::class.'::yieldCallback("'.$tagName.'"); ?>';
    },

    /**
     * @method before yield directive
     * @param string $tagName
     * @return string
     */
    'before-yield' => function(string $tagName) : string 
    {
        return '<!-- before-yield('.$tagName.') -->' . "\n";
    },

    /**
     * @method yield child 
     * @return string
     */
    'yield-child' => function(string $tagName = '') : string 
    {
        return '<!-- yield child('.$tagName.') -->';
    },

    /**
     *@method php opening tag directive
     *@return string
     */

    'php' => function() : string
    {
        return '<?php ' . "\n // PHP starts here \n";
    },

    /**
     *@method http method directive
     *@return string
     */

    'method' => function(string $method = 'post') : string
    {
        return '<input type="hidden" name="REQUEST_METHOD" value="'.$method.'"/>';
    },

    /**
     *@method php closing tag directive
     *@return string
     */
    'endphp' => function() : string
    {
        return "\n // PHP ends here \n ?>";
    },

    /**
     *@method html directive. close php tag
     *@return string
     */

    'html' => function() : string
    {
        return '?>' . "\n";
    },

    /**
     *@method html directive. open php tag
     *@return string
     */
    'endhtml' => function() : string
    {
        return '<?php ' . "\n // PHP starts here \n";
    },

    /**
     * @method post directive 
     * @return string
     */
    'post' => function(string $field, string $default = '') : string 
    {
        // @var post
        $post = post();

        if ($post->has($field)) $default = $post->get($field);

        // load default
        return $default;
    },

    /**
     * @method csrf error 
     * @return string
     */
    'csrf-error' => function() : string
    {
        // get error
        $error = \Lightroom\Common\Functions\csrf_error();

        // load error
        if ($error != '') return '<div class="alert alert-danger">'.$error.'</div>';

        // return empty string
        return '';
    },

    /**
     * @method view url
     * @return string
     */
    'view' => function(...$arguments) : string 
    {
        // get the incoming url
        $request = URL::getIncomingUri();
        
        // get the controller and view
        $controllerView = array_splice($request, 0, 2);

        // join with argument
        $request = array_merge($controllerView, $arguments);

        // return request
        return func()->url() . implode('/', $request);
    },

    /**
     * @method controller url
     * @return string
     */
    'controller' => function($path = '') : string 
    {
        // get the incoming url
        $request = URL::getIncomingUri();
        
        // get the controller and view
        $controller = $request[0];

        // return request
        return func()->url($controller . '/' . $path);
    },

    /**
     * @method timeAgo
     * @param string|int $time
     * @return string
     */
    'timeAgo' => function($time) : string 
    {
        return func()->timeAgo($time);
    },

    /**
     * @method link
     * @param string $target
     * @return string
     */
    'link' => function(string $target) : string 
    {
        return func()->url($target);
    },

    /**
     * @method input_error
     * @param string $field
     * @return void
     */
    'input_error' => function(string $field) 
    {
        Listener::ev('filter.showError', function(array $errors) use ($field)
        {
            // check if field has an error
            if (isset($errors[$field])) :

                // get errors
                $errors = $errors[$field];

                // build error
                foreach ($errors as $error) :

                    // show error
                    echo '<span class="input_error" style="display:block;">'.$error.'</span>';

                endforeach;

            endif;
        });
    },

    /**
     * @method shouldPrint
     * @param string $variable
     * @return string 
     */
    'shouldPrint' => function(string $variable) : string
    {
        // Remove quote
        $variable = preg_replace('/[\'|"]/', '', $variable);

        // @return string
        return "<?php if(isset(\$$variable)) :

                // get variable type
                switch (gettype(\$$variable)) :

                    case 'string':
                    case 'number':
                    case 'integer':
                        echo \$$variable;
                    break;

                    default:
                        var_dump(\$$variable);
                endswitch;

            endif;
        ?>";
    }
],


/**
 *@package Injecting all directives from a class
 *@return void
 */
function() : void
{
    // $this->inject(<class name>);  
    $this->inject(Lightroom\Database\Directives::class); 
});
