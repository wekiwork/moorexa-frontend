<?php
namespace Lightroom\Templates\Happy\Web\Engines\Interfaces;

/**
 * @package Engine Interface
 * @author Amadi Ifeanyi <amadiify.com>
 */
interface EngineInterface
{
    /**
     * @method EngineInterface setInterpreter
     * @param Interpreter $instance
     * @return void 
     */
    public static function setInterpreter($instance) : void;

    /**
     * @method EngineInterface initEngine
     * @param string $content
     * @return string 
     */
    public static function initEngine(string $content) : string;
}