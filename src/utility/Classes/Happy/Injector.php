<?php
namespace Happy;

// include DirectivesInterface
include_once 'DirectivesInterface.php';

/**
 * @package Happy Directive Injector
 * @author Amadi ifeanyi <amadiify.com>
 */
trait Injector
{
	// instance
	public $directiveInstance;

	/**
	 *@method Injector inject
	 *@return void
	 */
	public function inject(...$class)
	{
		// load and inject classes
		foreach ($class as $className) :
        
            // update class name
			$className = '\\'.$className;

            if (!class_exists($className)) throw new \Exception('Sorry we could not find class '. $className);
            
            // create reflection class
            $reflection = new \ReflectionClass($className);

            // check if class implements DirectivesInterface
            if (!$reflection->implementsInterface(DirectivesInterface::class)) :
                throw new \Exception('It appears that class "'.$className.'" does not implement "'.DirectivesInterface::class.'" interface. This action is required.');
            endif;

            // @var DirectivesInterface 
            $this->directiveInstance = $reflection->newInstanceWithoutConstructor();

            // call directives
            call_user_func($className.'::directives', $this);
            
            
		endforeach;
	}

	/**
     * @method Injector set
     * @param string $directive
     * @param string $method
     * 
     * This method sets a directive
     */
	public function set(string $directive, string $method) : Directives
	{
		if (method_exists($this->directiveInstance, $method)) :
		
			$build = get_class($this->directiveInstance) . '::' . $method;

			// create callable function
			$callable = function($arguments, $attrline) use ($method)
			{
				return call_user_func_array([$this->directiveInstance, $method], func_get_args());
			};

			// push to Directives 
            Directives::$directives[$directive] = $build;
            
		endif;

		return $this;
	}
}