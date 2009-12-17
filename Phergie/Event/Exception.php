<?php

/**
 * Exception related to outgoing events. 
 */
class Phergie_Event_Exception extends Phergie_Exception
{
    /**
     * Error indicating that an attempt was made to create an event of an 
     * unknown type
     */
    const ERR_UNKNOWN_EVENT_TYPE = 1;
}
