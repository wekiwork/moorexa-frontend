<?php
namespace Lightroom\Templates\Functions;

use Closure;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Lightroom\Vendor\Twig\TwigEngine;

/**
 * @method TwigEngine twig
 * @param Closure $closure
 * @return Environment
 */
function twig(Closure $closure = null) : Environment
{
    // load closure
    if (!is_null($closure) && is_callable($closure)) :

        // call closure function
        call_user_func($closure->bindTo(func()->TwigEngine(), TwigEngine::class));
        
    endif;

    // return environment
    return func()->TwigEngine()->environment;
}

/**
 * @method TwigEngine twigSystem
 * @return FilesystemLoader
 */
function twigSystem() : FilesystemLoader
{
    // return file system loader
    return func()->TwigEngine()->loader;
}
