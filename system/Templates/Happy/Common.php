<?php
namespace Lightroom\Templates\Happy;

use Lightroom\Templates\Happy\Interfaces\CommonInterface;
use Lightroom\Templates\Happy\Web\Engine;
use Lightroom\Templates\Happy\Web\Caching;
use Lightroom\Templates\Interfaces\TemplateEngineInterface;
use function Lightroom\Requests\Functions\{session};

/**
 * @package Common methods for Happy Engine
 * @author Amadi Ifeanyi <amadiify.com>
 */
trait Common
{
    /**
     * @var TemplateEngineInterface $instance
     */
    private static $instance;

    /**
     * @var bool $lockedOut
     * 
     * This would be handy when we need to lock further calls to our class methods.
     */
    private $lockedOut = false;

    /**
     * @var string $alaiseUsed
     */
    private $alaiseUsed = '';

    /**
     * @method TemplateEngineInterface init
     * @return void
     * 
     * This method would be called after registering template engine
     */
    public function init() : void
    {
        // save instance
        self::$instance = $this;

        // load engine functions and make available publicly
        include_once __DIR__ . '/'. $this->getBase() . '/Functions.php';
    }

    /**
     * @method Engine getInstance
     * @return TemplateEngineInterface
     * 
     * This method returns the current instance of this engine.
     */
    public static function getInstance() : TemplateEngineInterface
    {
        return self::$instance;
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
        // check if method exists and then make a call 
        if (method_exists($this, $method)) return call_user_func_array([$this, $method], $arguments);
    }

    /**
     * @method TemplateEngineInterface aliaseUsed
     * @param string $alaise
     * @return void
     * 
     * This method would register the alaise used for this template engine
     */
    public function aliaseUsed(string $alaise) : void
    {
        $this->aliaseUsed = $alaise;
    }

    /**
     * @method Engine removeAlaise
     * @param string $path
     * @return string
     */
    public function removeAlaise(string $path) : string 
    {
        if ($this->aliaseUsed !== '') :

            // remove alaise
            $path = str_replace('.' . $this->aliaseUsed, '', $path);

        endif;

        // return $path
        return $path;
    }

    /**
     * @method Engine json
     * @param array $jsonArray
     * @return void
     */
    public function json(array $jsonArray) : void
    {
        // not locked out
        if ($this->notLockedOut()) :

            // clean buffer
            ob_clean();

            // set response code
            http_response_code(200);

            // change content types
            header('Content-Type: application/json');

            // print json data
            echo json_encode($jsonArray, JSON_PRETTY_PRINT);

            // lock out
            $this->lockCalls();

            // kill the script
            die();

        endif;
    }

    /**
     * @method Common redirect
     * @param string $path
     * @param array $arguments
     * @return mixed
     */
    public function redirect(string $path = '', array $arguments = []) 
    {
        if ($path != '') :

            // set the response code
            http_response_code(301);
            
            // not external link
            if (!preg_match("/(:\/\/)/", $path)) :

                // get query
                $query = isset($arguments['query']) && is_array($arguments['query']) ? '?' . http_build_query($arguments['query']) : '';

                // get redirect data
                $data = [];

                // check query
                if (strlen($query) > 3) :

                    // check for data in arguments
                    $data = isset($arguments['data']) && is_array($arguments['data']) ? $arguments['data'] : [];

                else:

                    // data would be arguments here
                    $data = $arguments;

                endif;


                // get current request
                $currentRequest = ltrim($_SERVER['REQUEST_URI'], '/');

                // trigger redirection 
                if (event()->canEmit('ev.redirection')) event()->emit('ev', 'redirection', [
                    'path' => &$path,
                    'query' => &$query,
                    'data' => &$data
                ]);

                // add query to path
                $pathWithQuery = $path . $query;

                // redirect if pathWithQuery is not equivalent to the current request
                if ($pathWithQuery != $currentRequest) :

                    // export data
                    if (count($data) > 0) :

                        // get redirect data
                        $redirectData = session()->get('redirect.data');

                        // create array if not found
                        if (!is_array($redirectData)) $redirectData = [];

                        // lets add path
                        $redirectData[$pathWithQuery] = $data;

                        // set redirect data
                        session()->set('redirect.data', $redirectData);

                    endif;

                    // start buffer
                    ob_start();

                    // perform redirection
                    header('location: '. func()->url($pathWithQuery), true, 301); exit;

                endif;

            else:

                // build query
                $query = http_build_query($arguments);

                // check length
                $query = strlen($query) > 1 ? '?' . $query : $query;

                // trigger redirection 
                if (event()->canEmit('ev.redirection')) event()->emit('ev', 'redirection', [
                    'path' => &$path,
                    'query' => &$query,
                    'data' => &$data
                ]);

                // start buffer
                ob_start();

                // redirect to external link
                header('location: ' . $path . $query, true, 301); exit;

            endif;

        else:   

            // return object
            return new class()
            {
                /**
                 * @var array $exported
                 */
                private $exported = [];

                // load exported data
                public function __construct()
                {
                    // get current request
                    $currentRequest = ltrim($_SERVER['REQUEST_URI'], '/');

                    if (session()->has('redirect.data')) :

                        // @var array $data
                        $data = session()->get('redirect.data');

                        // check for exported data for current request
                        if (isset($data[$currentRequest])) :

                            // set
                            $this->exported = $data[$currentRequest];

                            // clean up
                            unset($data[$currentRequest]);

                            // set session again
                            session()->set('redirect.data', $data);

                        endif;

                    endif;
                }

                /**
                 * @method Common data
                 * @return array
                 */
                public function data() : array 
                {
                    return $this->exported;
                }

                /**
                 * @method Common has
                 * @param array $arguments
                 * @return bool
                 */
                public function has(...$arguments) : bool 
                {
                    // @var int $found
                    $found = 0;

                    // @var bool $has 
                    $has = false;

                    // check now
                    foreach ($arguments as $name) if (isset($this->exported[$name])) $found++;

                    //compare found
                    if (count($arguments) == $found) $has = true;

                    // return bool
                    return $has;
                }

                /**
                 * @method Common get
                 * @return mixed
                 */
                public function get(string $name) 
                {
                    return isset($this->exported[$name]) ? $this->exported[$name] : null;
                }

                /**
                 * @method Common __get
                 * @param string $name
                 * @return mixed
                 */
                public function __get(string $name) 
                {
                    // return value
                    return $this->get($name);
                }
            };

        endif;
    }

    /**
     * @method CommonInterface lockCalls
     * @return void
     * 
     * This lock calls to class methods
     */
    public function lockCalls() : void
    {
        $this->lockedOut = true;
    }

    /**
     * @method CommonInterface unlockCalls
     * @return void
     * 
     * This unlockss calls to class methods
     */
    public function unlockCalls() : void
    {
        $this->lockedOut = false;
    }

    /**
     * @method CommonInterface notLockedOut
     * @return bool
     * 
     * This would return true if calls to class methods hasn't been locked
     */
    public function notLockedOut() : bool
    {
        return ($this->lockedOut === false) ? true : false;
    }

    /**
     * @method CommonInterface lockedOut
     * @return bool
     * 
     * This would return true if calls to class methods has been locked
     */
    public function lockedOut() : bool
    {
        return ($this->lockedOut === true) ? true : false;
    }

    /**
     * @method Common extends
     * @param array $engines
     * @return void
     */
    public function extends(...$engines) : void 
    {
        Caching::registerExtendedEngines($engines);
    }

    /**
     * @method Common extendVariables
     * @param array $variables
     * @return void
     */
    public static function extendVariables(array $variables) : void 
    {
        Caching::$variablesExported = $variables;
    }

    /**
     * @method Common getVariablesExported
     * @return array 
     */
    public static function getVariablesExported() : array 
    {
       return Caching::$variablesExported;
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
        return $content;
    }
}