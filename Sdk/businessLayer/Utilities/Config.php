<?php

/**
 * Read Config files
 *
 * @author ideatarmac.com
 */

namespace BusinessLayer\Netpay\Utilities;

class Config
{

    /**
     * @var string
     */
    protected $file = '';
    /**
     * @var array
     */
    protected $fileArray = [];
    /**
     * @var string
     */
    protected $fileContent = '';
    
    /**
     * Reads the specified file, storing the file name in $this->file, output of parse_ini_file() in $this->fileArray,
     * and then running $this->generateFile() afterwards. Full/relative path to the INI file to read.
     *
     * @param string $parFile
     * @return bool
     */
    protected function readFile($parFile = null)
    {
        if ($parFile == null) {
            $parFile = $this->file;
        }

        // Input validation
        if ($parFile == null) {
            throw new \BadFunctionCallException("Parameter File must not be null if no filepath has previously been defined");
        }
        if (!is_file($parFile)) {
            throw new \RuntimeException("File '{$parFile}' does not exist or is inaccessable");
        }
        if (!is_readable($parFile)) {
            throw new \RuntimeException("File '{$parFile}' is not readable");
        }

        // Load and attempt to parse the INI file
        $array = parse_ini_file($parFile, true);
        if ($array === false) {
            throw new \RuntimeException("Failed to parse '{$parFile}' as an INI file");
        }

        // Set object variables
        $this->file      = $parFile;
        $this->fileArray = $array;
        $this->generateFile();

        // Indicate success
        return true;
    }
    
    /**
     * Generates a fully formatted INI string and stores it in $this->fileContent
     *
     * @return bool
     */
    protected function generateFile()
    {
        $this->fileContent = '';

        // Convert array into formatted INI string
        foreach ($this->fileArray as $section => $block) {
            // Section header, on its own line
            $this->fileContent .= "[{$section}]\r\n";

            // Enter each key=value pair on separate lines
            foreach ($block as $key => $value) {

                // checking $value is string or array
                if (is_array($value)) {
                    foreach ($value as $index => $string) {
                        $this->fileContent .= "{$key}={$string}\r\n";
                    }
                } else {
                    $this->fileContent .= "{$key}={$value}\r\n";
                }
            }

            // Blank lines between sections/at the end of the file
            $this->fileContent .= "\r\n";
        }

        // Indicate success
        return true;
    }
}
