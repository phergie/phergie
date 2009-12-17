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
     * Connection on which the event occurred 
     *
     * @var Phergie_Connection 
     */
    protected $_connection;

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

    /**
     * Sets the connection on which the event was received. 
     *
     * @param Phergie_Connection $connection
     * @return Phergie_Event_Abstract Provides a fluent interface 
     */
    public function setConnection(Phergie_Connection $connection)
    {
        if (empty($this->_connection)) {
            $this->_connection = $connection;
        }
        return $this;
    }

    /**
     * Returns the connection on which the event was received. 
     *
     * @return Phergie_Connection
     */
    public function getConnection()
    {
        return $this->_connection;
    }
}
