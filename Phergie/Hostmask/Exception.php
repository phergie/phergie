<?php

/**
 * Exception related to hostmask handling.
 */
class Phergie_Hostmask_Exception extends Phergie_Exception
{
    /**
     * Error indicating that an invalid hostmask string was specified
     */
    const ERR_INVALID_HOSTMASK = 1;
}
