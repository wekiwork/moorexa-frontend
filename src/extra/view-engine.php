<?php
namespace Moorexa\View;

use Closure;
use Happy\Directives;
use Lightroom\Adapter\ClassManager;
use Lightroom\Packager\Moorexa\MVC\{
    Helpers\ControllerLoader, View
};
use Lightroom\Vendor\Twig\TwigEngine;
use Lightroom\Vendor\Latte\LatteEngine;
use Lightroom\Core\BootCoreEngine;
use Lightroom\Vendor\Blade\BladeEngine;
use function Lightroom\Templates\Functions\{
    controller, viewVariables
};
use Lightroom\Vendor\Mustache\MustacheEngine;
use function Lightroom\Functions\GlobalVariables\var_get;
use Lightroom\Templates\Happy\Web\Engine as HappyWebEngine;
use Lightroom\Packager\Moorexa\Helpers\Hyphe;
use Lightroom\Events\Dispatcher;

/**
 * @package Moorexa view engine registry
 * @author Amadi Ifeanyi <amadiify.com>
 */
class Engine extends ControllerLoader
{
    /**
     * @method Engine loadAll
     * @return Closure
     * 
     * This method would load all view template engines
     */
    public static function loadAll() : Closure
    {
        // get class instance
        $instance = ClassManager::singleton(Engine::class);

        return function($view) use ($instance)
        {
            // register Happy template engine
            $view->registerEngine(HappyWebEngine::class, $view->aliase('h'), $instance->happyWebConfig())->default();

            // register twig template engine
            // you would have to install twig. See documentation
            //$view->registerEngine(TwigEngine::class, $view->aliase('twig'), $instance->twigConfig());

            // register mustache template engine
            // you would have to install Mustache. See documentation
            //$view->registerEngine(MustacheEngine::class, $view->aliase('mustache'), $instance->twigConfig());

            // register blade template engine
            // you would have to install Blade. See documentation, This doesn't function well
            // Would need custom fix on the library itself.
            //$view->registerEngine(BladeEngine::class, $view->aliase('blade'), $instance->bladeConfig());

            // register latte template engine
            // you would have to install Latte. See documentation, This doesn't function well
            //$view->registerEngine(LatteEngine::class, $view->aliase('latte'), $instance->latteConfig());
        };
    }
    
    /**
     * @method Engine happyWebConfig
     * @return Closure
     * 
     * This method would return a closure function that contains the desired configuration for this view engine 
     */
    public function happyWebConfig() : Closure
    { 
        // @var Engine $instance
        $instance = $this;

        // return closure
        return function() use (&$instance) {
        
            // include happy functions
            include_once 'happy-functions.php';

            // make bulk configuration
            $this->bulkConfiguration($instance->defaultConfiguration());

        };
    }

    /**
     * @method Engine twigConfig
     * @return Closure
     */
    public function twigConfig() : Closure
    {
        // @var Engine $instance
        $instance = $this;

        // return closure
        return function() use (&$instance)
        {
            // @var string $controller
            $controller = ucfirst(var_get('incoming-url')[0]);

            // @var string $base
            $base = ControllerLoader::basePath() . '/' . $controller . '/';

            // load template base
            $this->setTemplateBaseDirectory($base . '/Views/');

            // laod compilation cache directory
            $this->setCompilationDirectory(get_path(func()->const('storage'), '/Caches/Twig/'));

            // set file extension
            $this->setFileExtension('html');

            // make bulk configuration
            $this->bulkConfiguration($instance->defaultConfiguration());
        };
    }

    /**
     * @method Engine mustacheConfig
     * @return Closure
     */
    public function mustacheConfig() : Closure
    {
        // @var Engine $instance
        $instance = $this;

        // return closure
        return function() use (&$instance)
        {
            // @var string $controller
            $controller = ucfirst(var_get('incoming-url')[0]);

            // @var string $base
            $base = ControllerLoader::basePath() . '/' . $controller . '/';

            // load template base
            $this->setTemplateBaseDirectory($base . '/Views/');

            // laod compilation cache directory
            $this->setCompilationDirectory(get_path(func()->const('storage'), '/Caches/Mustache/'));

            // set file extension
            $this->setFileExtension('html');

            // make bulk configuration
            $this->bulkConfiguration($instance->defaultConfiguration());
        };
    }

    /**
     * @method Engine bladeConfig
     * @return Closure
     */
    public function bladeConfig() : Closure
    {
        // @var Engine $instance
        $instance = $this;

        // return closure
        return function() use (&$instance)
        {
            // @var string $controller
            $controller = ucfirst(var_get('incoming-url')[0]);

            // @var string $base
            $base = ControllerLoader::basePath() . '/' . $controller . '/';

            // load template base
            $this->setTemplateBaseDirectory($base . '/Views/');

            // laod compilation cache directory
            $this->setCompilationDirectory(get_path(func()->const('storage'), '/Caches/Blade/'));

            // set file extension
            $this->setFileExtension('html');

            // make bulk configuration
            $this->bulkConfiguration($instance->defaultConfiguration());
        };
    }

    /**
     * @method Engine latteConfig
     * @return Closure
     */
    public function latteConfig() : Closure
    {
        // @var Engine $instance
        $instance = $this;

        // return closure
        return function() use (&$instance)
        {
            // @var string $controller
            $controller = ucfirst(var_get('incoming-url')[0]);

            // @var string $base
            $base = ControllerLoader::basePath() . '/' . $controller . '/';

            // load template base
            $this->setTemplateBaseDirectory($base . '/Views/');

            // laod compilation cache directory
            $this->setCompilationDirectory(get_path(func()->const('storage'), '/Caches/Latte/'));

            // set file extension
            $this->setFileExtension('html');

            // make bulk configuration
            $this->bulkConfiguration($instance->defaultConfiguration());
        };
    }

    /**
     * @method Engine defaultConfiguration
     * @return array
     */
    public function defaultConfiguration() : array 
    {
        // @var string $controller
        $controller = ucfirst(var_get('incoming-url')[0]);

        // @var string $base
        $base = ControllerLoader::basePath() . '/' . $controller . '/';

        // load view static assets bundle
        $viewManager = ClassManager::singleton(View::class)->loadBundle();

        // make session_token avaliable
        $viewManager->session_token = var_get('session_token');

        // get path
        $getPath = function($target) use (&$base, $controller)
        {
           $path = ControllerLoader::config('directory', $target, $controller);

           // trigger event
            Dispatcher::ev('view.getPath', [
                'path' => &$path, 
                'find' => $target, 
                'controller' => $controller
            ], function($data) use (&$path){
                // replace path
                $path = isset($data['path']) ? $data['path'] : $path;
            });

           // check if directory exists
           return is_dir($path) ? $path : $base . $path;
        };

        // return array
        return [
            'setViewDirectory'      => $getPath('view'),
            'setCustomDirectory'    => $getPath('custom'),
            'setStaticDirectory'    => $getPath('static'),
            'setPartialDirectory'   => $getPath('partial'),
            'fileExtension'         => 'html',
            'cacheFile'             => true,
            'plugins'               => [

                'headers' => function(){
                    // set default header file
                    $this->setDefaultFile(get_path(func()->const('components'), '/Template/header.html'));
                },
                'footers' => function(){
                    // set default footer file
                    $this->setDefaultFile(get_path(func()->const('components'), '/Template/footer.html'));
                },
                'views' =>   function(){
                    // template file
                    $this->loadTemplateFile(function(string $viewpath) : string 
                    {
                        // get incoming url
                        $incomingUrl = var_get('incoming-url');

                        // get view helper
                        $helper = file_get_contents(get_path(func()->const('helper'), '/bodyofview.txt'));

                        // set header, viewpath and extension
                        $helper = str_replace("@@__path", $viewpath, $helper);
                        $helper = str_replace("@@__view", $incomingUrl[1], $helper);
                        $helper = str_replace("@@__cont", ucfirst($incomingUrl[0]), $helper);
                        $helper = str_replace("@@__filename", basename($viewpath), $helper);

                        // return string
                        return $helper;
                    });
                }
            ],
            'engines'               => [
                
                Directives::class => function(){
                    // register happy directives namespace 
                    BootCoreEngine::registerAliases([ Directives::class => get_path(func()->const('utility'), '/Classes/Happy/Directives.php') ]);
                    // include directive file
                    include_once __DIR__ . '/directives.php';
                },

                // Hyphe::class => function(){
                //     // register hyphe PXH namespace 
                // }
            ],
            'exportVariables'       => [
                'footers'   => [
                    // unpack bundle javascripts
                    'viewjs' => $viewManager->unpackJavascripts()
                ],
                'headers'   => [
                    // unpack bundle css
                    'viewCss' => $viewManager->unpackCss(), 
                    // unpack configuration
                    'package' => $viewManager->loadPackage()
                ],
                // return view variables when ready
                'views'     => function() : array { return viewVariables(); }
            ],
            'interpreter'   => [
                'namespaces'  => [
                    'function Lightroom\Templates\Functions\{controller}',
                    'function Lightroom\Requests\Functions\{session, cookie, get}',
                    'function Lightroom\Common\Functions\csrf'
                ],
                'replace' => [
                    '$this' => 'controller()',
                    '$provider' => 'controller()->provider'
                ]
            ]
        ];
    }
}