<?php

/**
 * Exception related to driver operations.
 */
class Phergie_Driver_Exception extends Phergie_Exception
{
    /**
     * Error indicating that an operation was requested requiring an active 
     * connection before one had been set
     */
    const ERR_NO_ACTIVE_CONNECTION = 1;

    /**
     * Error indicating that an operation was requested requiring an active 
     * connection where one had been set but not initiated
     */
    const ERR_NO_INITIATED_CONNECTION = 2;

    /**
     * Error indicating that an attempt to initiate a connection failed
     */
    const ERR_CONNECTION_ATTEMPT_FAILED = 3;
}
