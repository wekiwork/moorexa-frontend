<?php
namespace Happy;

/**
 * @package Happy Directives Interface
 * @author Amadi Ifeanyi <amadiify.com>
 */
interface DirectivesInterface
{
    /**
     * @method DirectivesInterface $instance
     * @return void
     * 
     * After implementing method, you should set the directive name and method with $instance->set(<directive>, <classmethod>)
     */
    public static function directives(Directives $instance) : void;
}