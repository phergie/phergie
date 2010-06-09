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
 * @package   Phergie_Plugin_Ping
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2010 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Plugin_Ping
 */

/**
 * Uses a self CTCP PING to ensure that the client connection has not been
 * dropped.
 *
 * @category Phergie
 * @package  Phergie_Plugin_Ping
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Plugin_Ping
 */
class Phergie_Plugin_Ping extends Phergie_Plugin_Abstract
{
    /**
     * Timestamp for the last instance in which an event was received
     *
     * @var int
     */
    protected $lastEvent;

    /**
     * Timestamp for the last instance in which a PING was sent
     *
     * @var int
     */
    protected $lastPing;

    /**
     * Initialize event timestamps upon connecting to the server.
     *
     * @return void
     */
    public function onConnect()
    {
        $this->lastEvent = time();
        $this->lastPing = null;
    }

    /**
     * Updates the timestamp since the last received event when a new event
     * arrives.
     *
     * @return void
     */
    public function preEvent()
    {
        $this->lastEvent = time();
    }

    /**
     * Clears the ping time if a reply is received.
     *
     * @return void
     */
    public function onPingResponse()
    {
        $this->lastPing = null;
    }

    /**
     * Performs a self ping if the event threshold has been exceeded or
     * issues a termination command if the ping threshold has been exceeded.
     *
     * @return void
     */
    public function onTick()
    {
        $time = time();
        if (!empty($this->lastPing)) {
            if ($time - $this->lastPing > $this->getConfig('ping.ping', 10)) {
                $this->doQuit();
            }
        } elseif (
            $time - $this->lastEvent > $this->getConfig('ping.event', 300)
        ) {
            $this->lastPing = $time;
            $this->doPing($this->getConnection()->getNick(), $this->lastPing);
        }
    }

    /**
     * Gets the last ping time
     * lastPing needs exposing for things such as unit testing
     *
     * @return int timestamp of last ping
     */
    public function getLastPing()
    {
        return $this->lastPing;
    }

    /**
     * Set the last ping time
     * lastPing needs to be exposed for unit testing
     * 
     * @param int|null $ping timestamp of last ping
     *
     * @return self
     */
    public function setLastPing($ping = null)
    {
        if (null === $ping) {
            $ping = time();
        }
        if (!is_int($ping)) {
            throw new InvalidArgumentException('$ping must be an integer or null');
        }
        $this->lastPing = $ping;
        return $this;
    }

    /**
     * Gets the last event time
     * lastEvent needs exposing for things such as unit testing
     *
     * @return int timestamp of last ping
     */
    public function getLastEvent()
    {
        return $this->lastEvent;
    }

    /**
     * Set the last event time
     * lastEvent needs to be exposed for unit testing
     *
     * @param int|null $event timestamp of last ping
     *
     * @return self
     */
    public function setLastEvent($event = null)
    {
        if (null === $event) {
            $event = time();
        }
        if (!is_int($event)) {
            throw new InvalidArgumentException('$ping must be an integer or null');
        }
        $this->lastEvent = $event;
        return $this;
    }
}
