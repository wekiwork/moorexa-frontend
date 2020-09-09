<?php
namespace Lightroom\Templates\Functions;

use Closure;
use Lightroom\Templates\Happy\Web\Engine as HappyWeb;
/**
 * @method HappyWeb Template Engine json
 * @param array $jsonArray
 * @return void
 */
function json(array $jsonArray) : void
{
    HappyWeb::getInstance()->json($jsonArray);
}

/**
 * @method HappyWeb happy
 * @param Closure $closure
 * @return HappyWeb
 */
function happy(Closure $closure = null) : HappyWeb
{
    // load closure
    if (!is_null($closure) && is_callable($closure)) :

        // get happy
        $happy = HappyWeb::getInstance();

        // call closure function
        call_user_func($closure->bindTo($happy, \get_class($happy)));
        
    endif;

    // return environment
    return HappyWeb::getInstance();
}

/**
 * @method HappyWeb export
 * @param array $data 
 * @return void
 */
function export(array $data) : void 
{
    // add to global variables
    HappyWeb::$globalVariables = array_merge(HappyWeb::$globalVariables, $data);
}

/**
 * @method HappyWeb fromExport
 * @param string $variable 
 * @return mixed
 */
function fromExport(string $variable) 
{
    // get global variables
    $globalVariables = HappyWeb::$globalVariables;

    // check if variable exists
    foreach ($globalVariables as $variableName => $variableValue) :

        // return variable value
        if ($variableName == $variable) return $variableValue;

    endforeach;
}