<?php
/**
 * Phergie 
 *
 * PHP version 5
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.
 * It is also available through the world-wide-web at this URL:
 * http://phergie.org/license
 *
 * @category  Phergie 
 * @package   Phergie
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2010 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie
 */

/**
 * Handles connections initiated by the bot.
 *
 * @category Phergie 
 * @package  Phergie
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie
 */
class Phergie_Connection_Handler implements Countable, IteratorAggregate
{
    /**
     * Map of connections indexed by hostmask
     *
     * @var array
     */
    protected $connections;

    /**
     * Constructor to initialize storage for connections. 
     *
     * @return void
     */
    public function __construct()
    {
        $this->connections = array();
    }

    /**
     * Adds a connection to the connection list.
     *
     * @param Phergie_Connection $connection Connection to add
     *
     * @return Phergie_Connection_Handler Provides a fluent interface
     */
    public function addConnection(Phergie_Connection $connection)
    {
        $this->connections[(string) $connection->getHostmask()] = $connection;
        return $this;
    }

    /**
     * Removes a connection from the connection list.
     *
     * @param Phergie_Connection|string $connection Instance or hostmask for
     *        the connection to remove
     *
     * @return Phergie_Connection_Handler Provides a fluent interface
     */
    public function removeConnection($connection)
    {
        if ($connection instanceof Phergie_Connection) {
            $hostmask = (string) $connection->getHostmask(); 
        } elseif (is_string($connection) 
            && isset($this->connections[$connection])) {
            $hostmask = $connection;
        } else {
            return $this;
        }
        unset($this->connections[$hostmask]);
        return $this;
    }

    /**
     * Returns the number of connections in the list. 
     *
     * @return int Number of connections 
     */
    public function count()
    {
        return count($this->connections);
    }

    /**
     * Returns an iterator for the connection list. 
     *
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->connections);
    }

    /**
     * Returns a list of specified connection objects.
     *
     * @param array|string $keys One or more hostmasks identifying the 
     *        connections to return
     *
     * @return array List of Phergie_Connection objects corresponding to the 
     *         specified hostmask(s)
     */
    public function getConnections($keys)
    {
        $connections = array();

        if (!is_array($keys)) {
            $keys = array($keys);
        }
        
        foreach ($keys as $key) {
            if (isset($this->connections[$key])) {
                $connections[] = $this->connections[$key];
            }
        }

        return $connections;
    }
}
