<?php
namespace Lightroom\Templates\Happy\Web\Plugins;

/**
 * @package Plugins Headers
 * @author Amadi Ifeanyi <amadiify.com>
 */
class Headers
{
    /**
     * @var string $defaultFile
     */
    private $defaultFile = '';
    
    /**
     * @var bool $headerSilent
     */
    private $headerSilent = false;

    /**
     * @var string $headerBaseFile
     */
    private $headerBaseFile = '';

    /**
     * @var bool $fileWasIntended
     */
    private $fileWasIntended = false;

    /**
     * @method Headers setDefaultFile
     * @param string $file
     * @return void
     */
    public function setDefaultFile(string $file) : void
    {
        $this->defaultFile = $file;
    }

    /**
     * @method Headers silent
     * @return void
     * 
     * This method silents the use of this plugin
     */
    public function silent() : void 
    {
        $this->headerSilent = true;
    }

    /**
     * @method Headers load
     * @param string $baseFile
     * @return void
     * 
     * This method sets a base file during run time.
     */
    public function load(string $baseFile) : void 
    {
        $this->headerBaseFile = $baseFile;
    }
    
    /**
     * @method Headers loadBaseFile
     * @param string $baseFile
     * @return mixed
     */
    public function loadBaseFile(string $directory, string $extension) 
    {
        // just perharps there is a change
        if ($this->headerBaseFile != '') :

            // update file was intended
            $this->fileWasIntended = true;

            // is it a file ? then stop execution
            if (is_file($this->headerBaseFile)) return;

            // build a list of possibilities
            $options = [
                $directory . '/' . $this->headerBaseFile,
                $directory . '/' . $this->headerBaseFile . '.' . $extension,
                                   $this->headerBaseFile . '.' . $extension
            ];

            // now we check
            foreach ($options as $option) :

                if (file_exists($option)) $this->headerBaseFile = $option; return;

            endforeach;
            
        endif;

        // update file was intended
        $this->fileWasIntended = false;

        // set the default base file
        $this->headerBaseFile = $directory . '/header.' . $extension;
    }

    /**
     * @method Headers inspectFile
     * @return string
     */
    public function inspectFile() : string 
    {
        // @var string $header
        $header = '';

        // continue if header is not silent.
        if ($this->headerSilent === false) :

            if ($this->fileWasIntended) :

                // update header
                $header = $this->headerBaseFile;

            else:

                // check if basefile exists
                if ($this->headerBaseFile != '' && file_exists($this->headerBaseFile)) :
                    
                    // load header content
                    $headerContent = file_get_contents($this->headerBaseFile);

                    // check if base file is set as default
                    if (strpos($headerContent, '@setdefault') !== false) $header = $this->headerBaseFile;

                    // clean up
                    $headerContent = null;

                endif;

                // load default file
                if ($header == '') $header = $this->defaultFile;

            endif;

        endif;

        // return string
        return $header;
    }

}