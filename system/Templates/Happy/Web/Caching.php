<?php
namespace Lightroom\Templates\Happy\Web;

use Lightroom\Templates\TemplateHandler;
use function Lightroom\Security\Functions\{encrypt, decrypt};
/**
 * @package Web Engine caching system
 * @author Amadi Ifeanyi <amadiify.com>
 */
class Caching
{
    /**
     * @var array $cached
     */
    private static $cached = [];

    /**
     * @var string $cacheFile
     */
    private static $cacheFile = __DIR__ . '/Caches/cached.json';

    /**
     * @var Interpreter instance
     */
    private static $interpreterInstance = null;

    /**
     * @var array $externalCachingEngines
     */
    public static $externalCachingEngines = [];

    /**
     * @var array $variablesExported
     */
    public static $variablesExported = [];

    /**
     * @method Caching cacheFile
     * @param string $path
     * @return string
     */
    public static function cacheFile(string $path) : string 
    {
        // we could end here if $path is'nt a file
        if (strlen($path) < 2 || !file_exists($path)) return '';

        // has file changed
        if (self::fileChanged($path)) :
            
            // @var boolean $canCache
            $canCache = true;

            // check env
            if (isset($_ENV['enableCaching']) && strtolower($_ENV['enableCaching']) == 'no') $canCache = false;

            // interpolate and cache file
            if ($canCache) : $path = self::interpolateAndCacheFile($path); endif;

        else:

            // get path
            $path = __DIR__ . self::$cached[md5($path)]->path;

        endif;

        // return string
        return $path;
    }

    /**
     * @method Caching registerExtendedEngines
     * @param array $engines 
     * @return void
     */
    public static function registerExtendedEngines(array $engines) : void 
    {
        self::$externalCachingEngines = $engines;
    }

    /**
     * @method Caching parseFromExternalEngine
     * @param string $content
     * @return void 
     */
    public static function parseFromExternalEngine(string $path, string &$content) : void 
    {
        // @var array $engines
        $engines = self::$externalCachingEngines;

        // check if any engine has been registered
        if (count($engines) > 0) :

            // build list
            foreach($engines as $index => $engine) $engines[$index] = "'$engine'";
            
            // build function
            $function = '<?=\Lightroom\Templates\Happy\Web\Caching::loadFromExternal(['.implode(',', $engines).'], "'.$path.'", "'.encrypt($content).'", serialize(get_defined_vars()) )?>';

            // replace content
            $content = $function;

        endif;
    }

    /**
     * @method Caching loadFromExternal
     * @param array $engines
     * @param string $path
     * @param string $encrypted
     * @return void
     */
    public static function loadFromExternal(array $engines, string $path, string $encrypted, string $variables) : void
    {   
        // update instance
        if (self::$interpreterInstance === null) self::$interpreterInstance = new Interpreter;

        // decrypt data
        $content = decrypt($encrypted);

        // get varriables
        $variables = unserialize($variables);

        // get template engine
        foreach ($engines as $engine) :

            // get engine class
            $engineClass = TemplateHandler::getTemplateHandler($engine);

            // read content
            $content = $engineClass->parseTextContent($content);

        endforeach;

        // interpolate 
        self::$interpreterInstance->interpolateExternal($content, $content);

        // add namespace from interpreter
        $content = Interpreter::loadNamespaces($content);

        // get path base
        $base = basename($path);

        // get directory
        $directory = rtrim($path, $base);

        // get user agent
        $agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'robot-in-the-building...';

        // generate lock file
        $filename = $directory . '/' . md5($path . '/' . $agent) . '.cache.lock';

        // save file content
        file_put_contents($filename, $content);

        // export variables
        extract($variables);

        // include file
        include_once $filename;
    }

    /**
     * @method Caching isCached
     * @param string $path
     * @return bool
     */
    private static function isCached(string $path) : bool 
    {
        // return bool
        return isset(self::loadCached()[md5($path)]) ? true : false;
    }

    /**
     * @method Caching loadCached
     * @return array
     */
    private static function loadCached() : array 
    {
        // @var array $caches
        $caches = self::$cached;

        // check if cached glob variable is empty
        if (count(self::$cached) == 0) :

            // load content
            if (file_exists(self::$cacheFile)) :

                // load json data
                $json = json_decode(trim(file_get_contents(self::$cacheFile)));

                // convert json to array
                if (is_object($json)) $caches = (array) $json;

            endif;

            // push global
            self::$cached = $caches;

        endif;

        // return array
        return $caches;
    }

    /**
     * @method Caching fileChanged
     * @param string $path
     * @return bool
     */
    private static function fileChanged(string $path) : bool 
    {
        // @var bool $filechanged
        $filechanged = true;

        // check if file has been cached
        if (self::isCached($path)) :

            // @var array $fileInformation
            $fileInformation = self::$cached[md5($path)];

            // get last modification time from cache
            $lastModified = $fileInformation->lastModified;

            // get current modification time
            $modificationTime = filemtime($path);

            // get content hash if file has just been updated
            if ($modificationTime > $lastModified) :

                // get content hash
                $contentHash = md5(file_get_contents($path));

                // compare $contentHash with cached hash
                if ($contentHash == $fileInformation->hash) :

                    // update file changed
                    $filechanged = false;

                    // update modification time
                    self::$cached[md5($path)]->lastModified = $modificationTime;

                    // continue if file is writable
                    if (is_writable(self::$cacheFile)) :

                        // save file
                        file_put_contents(self::$cacheFile, json_encode(self::$cached, JSON_PRETTY_PRINT));

                    endif;

                endif;

            else:

                // update file changed
                $filechanged = false;

            endif;

        endif;

        // return bool
        return $filechanged;
    }

    /**
     * @method Caching interpolateAndCacheFile
     * @param string $path
     * @return string
     */
    private static function interpolateAndCacheFile(string $path) : string 
    {
        // @var string $cacheFile
        $cacheFile = $path;

        // update instance
        if (self::$interpreterInstance === null) self::$interpreterInstance = new Interpreter;

        // @var string interpolatedContent
        $interpolatedContent = '';

        // get file content
        $content = file_get_contents($path);

        if (count(self::$externalCachingEngines) == 0) :

            // interpolate 
            self::$interpreterInstance->interpolateExternal($content, $interpolatedContent);

        else:

            $interpolatedContent = $content;

        endif;

        // @var string $directory
        $directory = __DIR__ . '/Caches/';

        // try create tmp folder
        if (!is_dir($directory . 'Tmp/')) mkdir($directory . 'Tmp/');

        // cache file
        $cacheFile = 'Tmp/' . md5($path) . '.cache';

        // save path
        $savePath = $directory . $cacheFile;

        // @var string $content
        self::parseFromExternalEngine($savePath, $interpolatedContent);

        // add namespace from interpreter
        $interpolatedContent = Interpreter::loadNamespaces($interpolatedContent);

        // save file
        file_put_contents($savePath, $interpolatedContent);

        // update path
        $cacheFile = '/Caches/' . $cacheFile;

        // if ($original == $content) :

        // update cached.json
        self::$cached[md5($path)] = (object) [
            'lastModified' => filemtime($path),
            'hash' => md5($content),
            'path' => $cacheFile,
            'file' => $path
        ];

        // save now
        file_put_contents($directory . 'cached.json', json_encode(self::$cached, JSON_PRETTY_PRINT));

        // return string
        return __DIR__ . $cacheFile;
    }
}