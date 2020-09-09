<?php
namespace Lightroom\Templates\Happy\Web;

use Lightroom\Templates\Happy\{
    Common, Directories
};
use Lightroom\Templates\Happy\Interfaces\{
    CommonInterface, DirectoriesInterface
};
use Exception, Closure;
use ReflectionException;
use function Lightroom\Templates\Functions\controller;
use Lightroom\Templates\Interfaces\TemplateEngineInterface;
/**
 * @package Happy Web Template Engine
 * @author Amadi Ifeanyi <amadiify.com>
 */
class Engine implements TemplateEngineInterface, CommonInterface, DirectoriesInterface
{
    // load common trait
    use Common, Directories;

    /**
     * @var bool $enableCaching
     */
    public $enableCaching = false;

    /**
     * @var array $globalVariables
     */
    public static $globalVariables = [];
    
    /**
     * @var string $extension
     */
    private $extension = 'html';

    /**
     * @var array $plugins
     */
    private $pluginsLoaded = [];

    /**
     * @var array $variables
     */
    private $variables = [];

    /**
     * @var array $exportedVariables
     */
    private $exportedVariables = [];

    /**
     * @var array $subscribers
     */
    private static $subscribers = [];

    /**
     * @method TemplateEngineInterface init
     * @return void
     *
     * This method would be called after registering template engine
     * @throws ReflectionException
     */
    public function init() : void
    {
        // save instance
        self::$instance = $this;

        // load plugins
        $this->plugins(['Headers' => null, 'Footers' => null, 'Views' => null]);

        // load engine functions and make available publicly
        include_once __DIR__ . '/Functions.php';
    }

    /**
     * @method TemplateEngineInterface engines
     * @param array $engines
     * @return void
     * @throws ReflectionException
     */
    public function engines(array $engines) : void 
    {
        foreach ($engines as $engine => $closure) :

            // swap engine name
            if (is_numeric($engine)) $engine = $closure;

            // call closure function
            if ($closure !== null && is_callable($closure)) call_user_func($closure->bindTo($this, static::class));

            // check if it exists
            if (!class_exists($engine)) throw new Exception('Template engine "'.$engine.'" was not found.');

            // create reflection class
            $reflection = new \ReflectionClass($engine);

            // check if class implements the engine interface
            if (!$reflection->implementsInterface(Engines\Interfaces\EngineInterface::class)) :

                // throw exception
                throw new Exception('Class "'.$engine.'" does not implements "'.Engines\Interfaces\EngineInterface::class.'" interface. It is a required action, please do and try again.');

            endif;  

            // load to Interpreter
            Interpreter::$engines[] = $engine;

        endforeach;
    }

    /**
     * @method Engine plugins
     * @param array $plugins
     * @return void
     * @throws ReflectionException
     */
    public function plugins(array $plugins) : void 
    {
        foreach ($plugins as $plugin => $closureForInstance) :

            if (is_string($plugin)) :

                // @var string $pluginClass
                $pluginClass = 'Lightroom\Templates\Happy\Web\Plugins\\' . ucfirst($plugin);

                // if class exists
                if (class_exists($pluginClass)) :

                    // get plugin instance
                    $reflection = new \ReflectionClass($pluginClass);

                    // instance
                    $instance = $reflection->newInstanceWithoutConstructor();

                    // load closure
                    if ($closureForInstance !== null and is_callable($closureForInstance)) call_user_func($closureForInstance->bindTo($instance, \get_class($instance)));

                    // add class instance to plugins loaded
                    $this->pluginsLoaded[$plugin] = $instance;

                endif;

            endif;

        endforeach;
    }

    /**
     * @method Engine fromPlugin
     * @param string $plugin
     * @return mixed
     * @throws Exception
     */
    public function fromPlugin(string $plugin) 
    {
        if (!isset($this->pluginsLoaded[$plugin])) throw new Exception('Plugin "'.$plugin.'" was not loaded. It is also possible that we could not find this plugin.');

        // return plugin
        return $this->pluginsLoaded[$plugin];
    }

    /**
     * @method Engine hasPlugin
     * @param string $plugin
     * @return bool
     */
    public function hasPlugin(string $plugin) : bool
    {
        // return bool
        return isset($this->pluginsLoaded[$plugin]) ? true : false;
    }

    /**
     * @method Engine exportVariables
     * @param array $variables
     * @return void 
     */
    public function exportVariables(array $variables) : void 
    {
        $this->exportedVariables = $variables;
    }

    /**
     * @method CommonInterface getBase
     * @return string This method returns the base directory of the class
     *
     * This method returns the base directory of the class
     */
    public function getBase() : string
    {
        return 'Web/';
    }

    /**
     * @method Engine cacheFile
     * @param bool $switch
     * @return void
     */
    public function cacheFile(bool $switch) : void 
    {
        $this->enableCaching = $switch;
    }

    /**
     * @method Engine fileExtension
     * @param string $extension
     * @return void
     */
    public function fileExtension(string $extension) : void
    {
        $this->extension = $extension;
    }

    /**
     * @method Engine bulkConfiguration
     * @param array $config
     * @return void
     */
    public function bulkConfiguration(array $config) : void 
    {
        // set directory
        foreach ($config as $method => $parameter) :

            // call method
            if (method_exists($this, $method)) call_user_func([$this, $method], $parameter);

        endforeach;
    }

    /**
     * @method Engine render
     * @param string $path
     * @param mixed $data default to array
     * @return void
     * @throws Exception
     */
    public function render(string $path, ...$data) : void 
    {
        if ($this->notLockedOut()) :

            // lock calls to this method
            $this->lockCalls();

            // @var array $variables
            $this->variables = $this->getVariablesAndCallClosure($data);

            // start buffer
            ob_start();

            // include header
            $this->includeHeader();
            
            // include view
            $this->includeView($path);

            // include footer
            $this->includeFooter();

            // load subscribers
            $this->loadSubscribers();

            // get output
            if (defined('HIDE_HTML_OUTPUT')) :

                // save response
                $_SERVER['HTML_OUTPUT'] = base64_encode(ob_get_contents());

                // clean now
                ob_end_clean();
                
            endif;

        endif;  
    }

    /**
     * @method Engine interpreter
     * @param array $configuration
     * @return void
     */
    public function interpreter(array $configuration) : void 
    {
        // load external configuration
        Interpreter::$externalConfiguration = $configuration;
    }

    /**
     * @method Engine getVariablesAndCallClosure
     * @param array $arguments
     * @return array 
     */
    public function getVariablesAndCallClosure(array $arguments) : array 
    {
        // run closure from $arguments 
        foreach ($arguments as $parameter) :

            if (is_callable($parameter) && $parameter !== null) :
                // call closure 
                call_user_func($parameter->bindTo($this, static::class));
                break;
            endif;

        endforeach;

        // return variables
        return (isset($arguments[0]) && is_array($arguments[0])) ? $arguments[0] : [];
    }

    /**
     * @method Engine getExportedVariablesFor
     * @param string $plugin
     * @return array
     */
    public function getExportedVariablesFor(string $plugin) : array 
    {
        // @var array $variable
        $variables = [];

        // get from exportedVariables
        if (isset($this->exportedVariables[$plugin])) : 

            // update variables
            $variables = $this->exportedVariables[$plugin];

        endif;

        // return array 
        return is_callable($variables) ? call_user_func($variables) : $variables;
    }

    /**
     * @method Engine registerSubscriber
     * @param string $subscriberName
     * @param Closure $closure
     * @return void
     */
    public static function registerSubscriber(string $subscriberName, Closure $closure) : void 
    {
        if (!isset(self::$subscribers[$subscriberName])) :

            // register now
            self::$subscribers[$subscriberName] = $closure;
            
        endif;
    }

    /**
     * @method Engine loadSubscribers
     * @return void
     */
    private function loadSubscribers()
    {
        if (count(self::$subscribers) > 0) :

            // load now
            foreach (self::$subscribers as $subscriber) call_user_func($subscriber->bindTo($this, static::class));

        endif;
    }

    /**
     * @method Engine includeHeader
     * @return void
     *
     * Will include header for view if headers plugin is set.
     * @throws Exception
     */
    private function includeHeader() : void 
    {
        if ($this->hasPlugin('headers')) :

            // load base file
            $this->fromPlugin('headers')->loadBaseFile($this->getCustomDirectory(), $this->extension);

            // @var string $header
            $header =  $this->fromPlugin('headers')->inspectFile();

            // variables
            $variables = $this->getExportedVariablesFor('headers');

            // export variables for extend support
            Common::extendVariables(array_merge($variables, $this->variables, $this->getExportedVariablesFor('views')));

            // try to load from cache if allowed
            if ($this->enableCaching) $header = Caching::cacheFile($header);

            // include file
            call_user_func(function(string $header) use ($variables)
            {
                if ($header != '') :

                    // header is ready
                    if (event()->canEmit('ev.header.ready')) event()->emit('ev', 'header.ready', [
                        'variables' => &$variables,
                        'cache' => &$header
                    ]);

                    // extract global variables
                    extract(self::$globalVariables);

                    // extract view variables
                    extract($this->getExportedVariablesFor('views'));

                    // extract exported variables
                    extract($variables);

                    // extract variables
                    extract($this->variables);

                    // include header
                    include_once $header;

                endif;
                
            }, $header);

        endif;
    }

    /**
     * @method Engine includeView
     * @param string $path
     * @return void
     *
     * Will include view if views plugin is set.
     * @throws Exception
     */
    private function includeView(string $path) : void 
    {
        if ($this->hasPlugin('views')) :

            // remove alaise
            $path = $this->removeAlaise($path);

            // load view
            $viewPath = $this->fromPlugin('views')->loadView($path, $this->getViewDirectory(), $this->extension);

            // variables
            $variables = $this->getExportedVariablesFor('views');

            // export variables for extend support
            Common::extendVariables(array_merge($variables, $this->variables));

            // try to load from cache if allowed
            if ($this->enableCaching) $viewPath = Caching::cacheFile($viewPath);

            // include file
            call_user_func(function(string $viewPath) use ($variables)
            {
                if ($viewPath != '') :

                    // view is ready
                    if (event()->canEmit('ev.view.ready')) event()->emit('ev', 'view.ready', [
                        'variables' => &$variables,
                        'cache' => &$viewPath
                    ]);

                    // extract global variables
                    extract(self::$globalVariables);

                    // extract exported variables
                    extract($variables);

                    // extract variables
                    extract($this->variables);

                    // include view
                    include_once $viewPath;

                endif;
                
            }, $viewPath);

        endif;
    }

    /**
     * @method Engine includeFooter
     * @return void
     *
     * Will include footer for view if footers plugin is set.
     * @throws Exception
     */
    private function includeFooter() : void 
    {
        if ($this->hasPlugin('footers')) :

            // load base file
            $this->fromPlugin('footers')->loadBaseFile($this->getCustomDirectory(), $this->extension);

            // @var string $footer
            $footer =  $this->fromPlugin('footers')->inspectFile();

            // variables
            $variables = $this->getExportedVariablesFor('footers');

            // export variables for extend support
            Common::extendVariables(array_merge($variables, $this->variables, $this->getExportedVariablesFor('views')));

            // try to load from cache if allowed
            if ($this->enableCaching) $footer = Caching::cacheFile($footer);

            // include file
            call_user_func(function(string $footer) use ($variables)
            {
                if ($footer != '') :

                    // footer is ready
                    if (event()->canEmit('ev.footer.ready')) event()->emit('ev', 'footer.ready', [
                        'variables' => &$variables,
                        'cache' => &$footer
                    ]);

                    // extract global variables
                    extract(self::$globalVariables);

                    // extract view variables
                    extract($this->getExportedVariablesFor('views'));
                    
                    // extract exported variables
                    extract($variables);

                    // extract variables
                    extract($this->variables);

                    // include footer
                    include_once $footer;

                endif;
                
            }, $footer);

        endif;
    }

}