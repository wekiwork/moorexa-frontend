<?php
namespace Lightroom\Templates\Happy;

/**
 * @package Happy Engine Directories Trait
 * @author Amadi Ifeanyi <amadiify.com>
 */
trait Directories
{
    /**
     * @var array $directories
     */
    private $directories = [];

    /**
     * @method DirectoriesInterface getDirectory
     * @param string $method
     * @return string
     */
    public function getDirectory(string $method) : string 
    {
        if (!isset($this->directories[$method])) throw new \Exception('Directory for "'.$method.'" has not been set.');

        // return string
        return $this->directories[$method];
    }

    /**
     * @method DirectoriesInterface setViewDirectory
     * @param string $directory
     * @return void
     */
    public function setViewDirectory(string $directory) : void 
    {
        $this->directories['ViewDirectory'] = $directory;
    }

    /**
     * @method DirectoriesInterface getViewDirectory
     * @return string
     */
    public function getViewDirectory() : string 
    {
        return $this->getDirectory('ViewDirectory');
    }

    /**
     * @method DirectoriesInterface setCustomDirectory
     * @param string $directory
     * @return void
     */
    public function setCustomDirectory(string $directory) : void 
    {
        $this->directories['CustomDirectory'] = $directory;
    }

    /**
     * @method DirectoriesInterface getCustomDirectory
     * @return string
     */
    public function getCustomDirectory() : string 
    {
        return $this->getDirectory('CustomDirectory');
    }

    /**
     * @method DirectoriesInterface setStaticDirectory
     * @param string $directory
     * @return void
     */
    public function setStaticDirectory(string $directory) : void 
    {
        $this->directories['StaticDirectory'] = $directory;
    }

    /**
     * @method DirectoriesInterface getStaticDirectory
     * @return string
     */
    public function getStaticDirectory() : string 
    {
        return $this->getDirectory('StaticDirectory');
    }

    /**
     * @method DirectoriesInterface setPartialDirectory
     * @param string $directory
     * @return void
     */
    public function setPartialDirectory(string $directory) : void 
    {
        $this->directories['PartialDirectory'] = $directory;
    }

    /**
     * @method DirectoriesInterface getPartialDirectory
     * @return string
     */
    public function getPartialDirectory() : string 
    {
        return $this->getDirectory('PartialDirectory');
    }
}