<?php

/**
 * Exception related to configuration.
 */
class Phergie_Config_Exception extends Phergie_Exception
{
    /**
     * Error indicating that an attempt was made to read a configuration 
     * file that could not be executed
     */
    const ERR_FILE_NOT_EXECUTABLE = 1;
}
