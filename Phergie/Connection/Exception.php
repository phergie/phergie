<?php

/**
 * Exception related to a connection to an IRC server.
 */
class Phergie_Connection_Exception extends Phergie_Exception
{
    /**
     * Error indicating that an operation was attempted requiring a value 
     * for a specific configuration setting, but none was set
     */
    const ERR_REQUIRED_SETTING_MISSING = 1;
}
