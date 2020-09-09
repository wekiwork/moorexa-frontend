<?php
namespace Lightroom\Templates\Functions;

use Closure;
use Mustache_Engine;
use Lightroom\Vendor\Mustache\MustacheEngine;

/**
 * @method TwigEngine twig
 * @param Closure $closure
 * @return Environment
 */
function mustache(Closure $closure = null) : Mustache_Engine
{
    // load closure
    if (!is_null($closure) && is_callable($closure)) :

        // call closure function
        call_user_func($closure->bindTo(func()->MustacheEngine(), Mustache_Engine::class));
        
    endif;

    // return loader
    return func()->MustacheEngine()->loader;
}
