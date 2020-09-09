<?php
namespace Lightroom\Templates\Functions;

use Closure;
use Latte\Engine;
use Lightroom\Vendor\Latte\LatteEngine;

/**
 * @method LatteEngine latte
 * @param Closure $closure
 * @return Engine
 */
function latte(Closure $closure = null) : Engine
{
    // load closure
    if (!is_null($closure) && is_callable($closure)) :

        // call closure function
        call_user_func($closure->bindTo(func()->LatteEngine(), Engine::class));
        
    endif;

    // return environment
    return func()->LatteEngine()->loader;
}
