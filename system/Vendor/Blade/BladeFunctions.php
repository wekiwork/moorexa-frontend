<?php
namespace Lightroom\Templates\Functions;

use Closure;
use Jenssegers\Blade\Blade;
use Lightroom\Vendor\Blade\BladeEngine;

/**
 * @method BladeEngine blade
 * @param Closure $closure
 * @return Blade
 */
function blade(Closure $closure = null) : Blade
{
    // load closure
    if (!is_null($closure) && is_callable($closure)) :

        // call closure function
        call_user_func($closure->bindTo(func()->BladeEngine(), Blade::class));
        
    endif;

    // return environment
    return func()->BladeEngine()->loader;
}
