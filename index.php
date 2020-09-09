<?php

use Lightroom\{
   Core\BootCoreEngine, Core\Payload, 
   Core\PayloadRunner, Adapter\ClassManager
};

// include the init file
require_once 'init.php';

/**
 * @package  Moorexa PHP Framework
 * @author   Fregatelab inc http://fregatelab.com
 * @author   Amadi ifeanyi <amadiify.com>
 * @version  0.0.1
 */

try {

   // create BootCoreEngine instance
   $engine = ClassManager::singleton(BootCoreEngine::class);

   // create Payload instance
   $payload = ClassManager::singleton(Payload::class)->clearPayloads();

   // display errors
   $engine->displayErrors(true);

   // apply default character encoding
   $engine->setEncoding('UTF-8');

   // apply default time zone
   $engine->setTimeZone('Africa/Lagos');

   // apply default content type
   $engine->setContentType('text/html');

   // clean output
   ob_end_clean();

   /**
    * Register a default package manager
    * @package Lightroom\Packager\Moorexa\MoorexaWebPackager 

    * This loads the default packager for the web.
    */
   $engine->defaultPackageManager($payload, $MAIN_PACKAGER);

   /**
    * Register a default package manager
    * @package Classes\Platforms\Launcher 

    * This launcher would enable you work with different platforms on the go.
    * But you can remove it if you want to stick with one platform only.
    */
   //$engine->defaultPackageManager($payload, Classes\Platforms\Launcher::class);

   // boot application
   $engine->bootProgram($payload, ClassManager::singleton(PayloadRunner::class)->clearPayloads());

} catch (\Lightroom\Exceptions\ClassNotFound $e) {}