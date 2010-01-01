<?php

/**
 * Base class for events.
 */
abstract class Phergie_Event_Abstract
{
    /**
     * Event type, used for determining the callback to execute in response
     *
     * @var string
     */
    protected $_type;

    /**
     * Returns the event type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->_type; 
    }

    /**
     * Sets the event type.
     *
     * @param string $type
     * @return Phergie_Event_Abstract Implements a fluent interface
     */
    public function setType($type)
    {
        $this->_type = (string) $type;
        return $this;
    }
}
