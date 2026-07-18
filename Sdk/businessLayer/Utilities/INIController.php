<?php
/**
 * API class that allows static usage of the master INI controller
 *
 * @author ideatarmac.com
 */

namespace BusinessLayer\Netpay\Utilities;

/**
 * API class that allows static usage of the master INI controller
 *
 * Provides a static interface for direct manipulation of INI files. All methods in this class directly read INI files.
 */
class INIController extends Config
{
    /**
     * Fetches the full INI file as a multi-level associative array in format $array['Section']['Key'] = 'Value'
     *
     * @param string $parFile
     * @return array
     */
    public static function fetchFile($parFile)
    {
        $self = new static;

        // Read the INI file
        $self->readFile($parFile);

        // Return the full array
        return $self->fileArray;
    }

    /**
     * Fetches an INI section from a file as an associative array in format $array['Key'] = 'Value'
     *
     * @param string $parFile
     * @param string $parSection
     * @return array|bool
     */
    public static function fetchSection($parFile, $parSection)
    {
        $self = new static;

        // Read the INI file
        $self->readFile($parFile);

        // Return the section, or false if the section doesn't exist
        if (isset($self->fileArray[$parSection])) {
            return $self->fileArray[$parSection];
        } else {
            return false;
        }
    }

    /**
     * Fetches the value of a requested key=value pair from an INI file.
     *
     * @param string $parFile
     * @param string $parSection
     * @param string $parKey
     * @return bool|string
     */
    public static function fetchKey($parFile, $parSection, $parKey)
    {
        $self = new static;

        // Read the INI file
        $self->readFile($parFile);

        // Return the key=value pair, or false if the entry doesn't exist
        if (isset($self->fileArray[$parSection][$parKey])) {
            return $self->fileArray[$parSection][$parKey];
        } else {
            return false;
        }
    }
}
