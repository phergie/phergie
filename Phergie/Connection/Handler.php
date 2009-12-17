<?php

/**
 * Handles connections initiated by the bot.
 */
class Phergie_Connection_Handler implements Countable, IteratorAggregate
{
    /**
     * Map of connections indexed by hostmask
     *
     * @var array
     */
    protected $_connections;

    /**
     * Constructor to initialize storage for connections. 
     *
     * @return void
     */
    public function __construct()
    {
        $this->_connections = array();
    }

    /**
     * Adds a connection to the connection list.
     *
     * @param Phergie_Connection $connection
     * @return Phergie_Connection_Handler Provides a fluent interface
     */
    public function addConnection(Phergie_Connection $connection)
    {
        $this->_connections[(string) $connection->getHostmask()] = $connection;
        return $this;
    }

    /**
     * Removes a connection from the connection list.
     *
     * @param Phergie_Connection|string $connection Instance or hostmask for
     *        the connection to remove
     * @return Phergie_Connection_Handler Provides a fluent interface
     */
    public function removeConnection($connection)
    {
        if ($connection instanceof Phergie_Connection) {
            $hostmask = (string) $connection->getHostmask(); 
        } elseif (is_string($connection) 
            && isset($this->_connections[$connection])) {
            $hostmask = $connection;
        } else {
            return $this;
        }
        unset($this->_connections[$hostmask]);
        return $this;
    }

    /**
     * Returns the number of connections in the list. 
     *
     * @return int Number of connections 
     */
    public function count()
    {
        return count($this->_connections);
    }

    /**
     * Returns an iterator for the connection list. 
     *
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->_connections);
    }
}
