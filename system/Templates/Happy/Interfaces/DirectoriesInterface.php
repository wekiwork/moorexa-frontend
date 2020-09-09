<?php
namespace Lightroom\Templates\Happy\Interfaces;

/**
 * @package Happy Template Engine Directories Interface
 * @author Amadi Ifeanyi <amadiify.com>
 */
interface DirectoriesInterface
{
    /**
     * @method DirectoriesInterface getDirectory
     * @param string $method
     * @return string
     */
    public function getDirectory(string $method) : string;

    /**
     * @method DirectoriesInterface setViewDirectory
     * @param string $directory
     * @return void
     */
    public function setViewDirectory(string $directory) : void;

    /**
     * @method DirectoriesInterface getViewDirectory
     * @return string
     */
    public function getViewDirectory() : string;

    /**
     * @method DirectoriesInterface setCustomDirectory
     * @param string $directory
     * @return void
     */
    public function setCustomDirectory(string $directory) : void;

    /**
     * @method DirectoriesInterface getCustomDirectory
     * @return string
     */
    public function getCustomDirectory() : string;

    /**
     * @method DirectoriesInterface setStaticDirectory
     * @param string $directory
     * @return void
     */
    public function setStaticDirectory(string $directory) : void;

    /**
     * @method DirectoriesInterface getStaticDirectory
     * @return string
     */
    public function getStaticDirectory() : string;

    /**
     * @method DirectoriesInterface setPartialDirectory
     * @param string $directory
     * @return void
     */
    public function setPartialDirectory(string $directory) : void;

    /**
     * @method DirectoriesInterface getPartialDirectory
     * @return string
     */
    public function getPartialDirectory() : string;
}