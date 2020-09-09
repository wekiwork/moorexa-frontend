<?php
namespace Lightroom\Vendor\Twig;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Lightroom\Adapter\ClassManager;
use Lightroom\Adapter\GlobalFunctions;
use Lightroom\Templates\Happy\Common;
use Lightroom\Templates\Happy\Web\Engine as HappyWeb;
use Lightroom\Templates\Happy\Web\Caching as HappyWebCaching;
use Lightroom\Templates\Interfaces\TemplateEngineInterface;
/**
 * @package Twig Template Engine
 * @author Amadi Ifeanyi <amadiify.com.
 */
class TwigEngine implements TemplateEngineInterface
{
    use Common;

    /**
     * @var FilesystemLoader $loader
     */
    public $loader;

    /**
     * @var Environment $environment
     */
    public $environment;

    /**
     * @var string $alaise
     */
    private $alaise = '';

    /**
     * @var string $baseDirectory
     */
    private $baseDirectory = '';

    /**
     * @var string $compilationDirectory
     */
    private $compilationDirectory = '';

    /**
     * @var string $fileExtension
     */
    private $fileExtension = 'html';

    /**
     * @var array $variables
     */
    private $variables = [];

    /**
     * @var array $environmentPaths
     */
    private static $environmentPaths = [];

    /**
     * @var array $globalVariables
     */
    private static $globalVariables = [];

    /**
     * @method TemplateEngineInterface init
     * @return void
     * 
     * This method would be called after registering template engine
     */
    public function init() : void
    {
        // set loader with base directory
        $this->loader = ClassManager::singleton(FilesystemLoader::class, $this->baseDirectory);

        // set compilation directory
        $this->environment = ClassManager::singleton(Environment::class, $this->loader, [
            'cache' => $this->compilationDirectory
        ]);

        // get closure function for class
        $closure = function(){ return $this; };

        // create function
        func()->create('TwigEngine', $closure->bindTo($this, static::class))->attachTo(GlobalFunctions::class);

        // get happy engine
        $happy = $this->loadEngine('happy');

        // add partial directory
        $this->loader->addPath($happy->getPartialDirectory(), 'partial');

        // add custom directory
        $this->loader->addPath($happy->getCustomDirectory(), 'custom');

        // add static directory
        $this->loader->addPath($happy->getStaticDirectory(), 'static');

        // load functions
        include_once 'TwigFunctions.php';
    }

    /**
     * @method TemplateEngineInterface externalCall
     * @param string $method
     * @param array $arguments
     * @return mixed
     *
     * This method would be called when there is an external method request. Possibly from the template handler
     */
    public function externalCall(string $method, ...$arguments)
    {
        // load from class
        if (method_exists($this, $method)) call_user_func_array([$this, $method], $arguments);
    }

    /**
     * @method TwigEngine setTemplateBaseDirectory
     * @param string $baseDirectory
     * @return void
     */
    public function setTemplateBaseDirectory(string $baseDirectory) : void 
    {
        // set base directory
        $this->baseDirectory = $baseDirectory;
    }

    /**
     * @method TwigEngine setCompilationDirectory
     * @param string $baseDirectory
     * @return void
     */
    public function setCompilationDirectory(string $compilationDirectory) : void 
    {
        // set compilation directory
        $this->compilationDirectory = $compilationDirectory;
    }

    /**
     * @method TwigEngine setFileExtension
     * @param string $fileExtension
     * @return void
     */
    public function setFileExtension(string $fileExtension) : void 
    {
        $this->fileExtension = $fileExtension;
    }

    /**
     * @method TwigEngine render
     * @param string $file 
     * @param array $arguments
     * @return void
     */
    public function render(string $file, ...$arguments) : void 
    {
        // clean file without alaise
        $fileName = $this->removeAlaise($file);

        // add extenstion to file
        if (strpos($fileName, '.') === false) $fileName .= '.' . $this->fileExtension;

        // render template
        if ($this->notLockedOut() && !defined('HIDE_HTML_OUTPUT')) :

            // lock calls
            $this->lockCalls();

            // @var array $variables
            $this->variables = $this->loadEngine('happy')->getVariablesAndCallClosure($arguments);

            // start buffer
            ob_start();

            // load header
            $this->includeHeader();

            // print out
            echo $this->environment->render($fileName, $this->variables);

            // load footer
            $this->includeFooter();

            // cache output
            $this->cacheOutput($fileName);

        endif;
    }

    /**
     * @method TwigEngine loadEngine
     * @param string $engine
     * @return TemplateEngineInterface
     */
    public function loadEngine(string $engine) : TemplateEngineInterface
    {
        switch ($engine) :

            // happy web engine
            case 'happy':
                return ClassManager::singleton(HappyWeb::class);
        
        endswitch;
    }

    /**
     * @method TwigEngine bulkConfiguration
     * @param array $configuration
     * @return mixed
     * 
     * load bulk configuration
     */
    public function bulkConfiguration(array $configuration)
    {
        $this->loadEngine('happy')->bulkConfiguration($configuration);
    }

    /**
     * @method TwigEngine loadCustomPath
     * @param string $path 
     * @param string &$basename
     * @return Environment
     */
    public function loadCustomPath(string $path, string &$basename = '') : Environment 
    {
        // get base name
        $basename = basename($path);

        // get directory
        $directory = rtrim($path, $basename);

        // load environment
        $environment = isset(self::$environmentPaths[$directory]) ? self::$environmentPaths[$directory] : null;

        // check if $environment is null
        if ($environment === null) :

            // loader
            $loader = new FilesystemLoader($directory);

            // load environment
            $environment = new Environment($loader, [
                'cache' => $this->compilationDirectory
            ]);

            // cache
            self::$environmentPaths[$directory] = $environment;

        endif;

        // return Environment
        return $environment;
    }

    /**
     * @method TemplateEngineInterface parseTextContent
     * @param string $content
     * @return string
     * 
     * This method would parse text content when extends function is called
     */
    public function parseTextContent(string $content) : string
    {
        $loader = new \Twig\Loader\ArrayLoader([
            'parseTextContent' => $content,
        ]);

        $twig = new \Twig\Environment($loader);

        // clean up
        $loader = null;

        // parse text content
        return (string) $twig->render('parseTextContent', self::getVariablesExported());
    }

    /**
     * @method TwigEngine includeHeader
     * @return void
     */
    private function includeHeader() : void 
    {
        // load happy engine
        $happy = $this->loadEngine('happy');

        // check for header plugin
        if ($happy->hasPlugin('headers')) :

            // load base file
            $happy->fromPlugin('headers')->loadBaseFile($happy->getCustomDirectory(), $this->fileExtension);

            // @var string $header
            $header =  $happy->fromPlugin('headers')->inspectFile();

            // load header
            if (is_string($header) && strlen($header) > 5) :

                // get variables
                $variables = $happy->getExportedVariablesFor('headers');

                // export variables
                self::$globalVariables = array_merge($variables, self::$globalVariables);

                // merge it with variables 
                $variables = array_merge($variables, $this->variables);

                // @var string $fileName
                $fileName = '';

                // load environment
                $environment = $this->loadCustomPath($header, $fileName);

                // print out
                echo $environment->render($fileName, $variables);

            endif;

        endif;
    }

    /**
     * @method TwigEngine includeFooter
     * @return void
     */
    private function includeFooter() : void 
    {
        // load happy engine
        $happy = $this->loadEngine('happy');

        // check for footer plugin
        if ($happy->hasPlugin('footers')) :

            // load base file
            $happy->fromPlugin('footers')->loadBaseFile($happy->getCustomDirectory(), $this->fileExtension);

            // @var string $footer
            $footer = $happy->fromPlugin('footers')->inspectFile();

            // load footer
            if (is_string($footer) && strlen($footer) > 5) :

                // get variables
                $variables = $happy->getExportedVariablesFor('footers');

                // export variables
                self::$globalVariables = array_merge($variables, self::$globalVariables);

                // merge it with variables 
                $variables = array_merge($variables, $this->variables);

                // @var string $fileName
                $fileName = '';

                // load environment
                $environment = $this->loadCustomPath($footer, $fileName);

                // print out
                echo $environment->render($fileName, $variables);

            endif;

        endif;
    }

    /**
     * @method TwigEngine cacheOutput
     * @param string $view
     * @return void
     */
    private function cacheOutput(string $view) : void 
    {
        // get output
        $output = ob_get_contents();

        // get view hash name
        $hashName = md5($this->loader->getPaths()[0] . $view);

        // load cache
        $cache = json_decode(trim(file_get_contents(__DIR__ . '/cache.json')));

        // convert null to array
        if (is_null($cache)) $cache = [];

        // convert object to array
        if (is_object($cache)) $cache = func()->toArray($cache);

        // @var bool $saveToFile
        $saveToFile = !isset($cache[$hashName]) ? true : false;

        // check content hash
        if (!$saveToFile) $saveToFile = ($cache[$hashName] != md5($output)) ? true : false;

        // get cache path for file
        $fileCachePath = $this->compilationDirectory . '/' . $view . '.cache';

        // can we save
        if ($saveToFile) :

            // add to cache.json
            $cache[$hashName] = md5($output);

            // save file
            file_put_contents(__DIR__ . '/cache.json', json_encode($cache, JSON_PRETTY_PRINT));

            // save output
            file_put_contents($fileCachePath, $output);

        endif;

        // try to load from cache if allowed
        $fileCachePath = HappyWebCaching::cacheFile($fileCachePath);

        // clear buffer
        ob_end_clean();

        // export variables
        extract(array_merge(self::$globalVariables, $this->variables));

        // include file
        if (file_exists($fileCachePath)) include_once $fileCachePath;
    }
} 