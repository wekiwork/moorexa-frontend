<?php
namespace Lightroom\Templates\Happy\Web\Plugins;

/**
 * @package Plugins Footers
 * @author Amadi Ifeanyi <amadiify.com>
 */
class Footers
{
    /**
     * @var string $defaultFile
     */
    private $defaultFile = '';
    
    /**
     * @var bool $footerSilent
     */
    private $footerSilent = false;

    /**
     * @var string $footerBaseFile
     */
    private $footerBaseFile = '';

    /**
     * @var bool $fileWasIntended
     */
    private $fileWasIntended = false;

    /**
     * @method Footers setDefaultFile
     * @param string $file
     * @return void
     */
    public function setDefaultFile(string $file) : void
    {
        $this->defaultFile = $file;
    }

    /**
     * @method Footers silent
     * @return void
     * 
     * This method silents the use of this plugin
     */
    public function silent() : void 
    {
        $this->footerSilent = true;
    }

    /**
     * @method Footers load
     * @param string $baseFile
     * @return void
     * 
     * This method sets a base file during run time.
     */
    public function load(string $baseFile) : void 
    {
        $this->footerBaseFile = $baseFile;
    }
    
    /**
     * @method Footers loadBaseFile
     * @param string $baseFile
     * @return mixed
     */
    public function loadBaseFile(string $directory, string $extension) 
    {
        // just perharps there is a change
        if ($this->footerBaseFile != '') :

            // update file was intended
            $this->fileWasIntended = true;

            // is it a file ? then stop execution
            if (is_file($this->footerBaseFile)) return;

            // build a list of possibilities
            $options = [
                $directory . '/' . $this->footerBaseFile,
                $directory . '/' . $this->footerBaseFile . '.' . $extension,
                                   $this->footerBaseFile . '.' . $extension
            ];

            // now we check
            foreach ($options as $option) :

                if (file_exists($option)) $this->footerBaseFile = $option; return;

            endforeach;
            
        endif;

        // update file was intended
        $this->fileWasIntended = false;

        // set the default base file
        $this->footerBaseFile = $directory . '/footer.' . $extension;
    }

    /**
     * @method Footers inspectFile
     * @return string
     */
    public function inspectFile() : string 
    {
        // @var string $footer
        $footer = '';

        // continue if footer is not silent.
        if ($this->footerSilent === false) :

            if ($this->fileWasIntended) :

                // update footer
                $footer = $this->footerBaseFile;

            else:

                // check if basefile exists
                if ($this->footerBaseFile != '' && file_exists($this->footerBaseFile)) :
                    
                    // load footer content
                    $footerContent = file_get_contents($this->footerBaseFile);

                    // check if base file is set as default
                    if (strpos($footerContent, '@setdefault') !== false) $footer = $this->footerBaseFile;

                    // clean up
                    $footerContent = null;

                endif;

                // load default file
                if ($footer == '') $footer = $this->defaultFile;

            endif;

        endif;

        // return string
        return $footer;
    }

}