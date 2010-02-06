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
 * Uses a self CTCP PING to ensure that the client connection has not been 
 * dropped.
 *
 * @category Phergie 
 * @package  Phergie
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie
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
    public function onPingReply()
    {
        $this->lastPing = null;
    }

    /**
     * Performs a self ping if the event threshold has been exceeded or 
     * issues a termination command if the ping theshold has been exceeded. 
     *
     * @return void
     */
    public function onTick()
    {
        $time = time();
        
        if (!empty($this->lastPing) 
            && $time - $this->lastPing > $this->getConfig('ping.ping')
        ) {
            $this->doQuit();
        } elseif ($time - $this->lastEvent > $this->getConfig('ping.event')) {
            $this->lastPing = time();
            $this->doPing($this->lastPing);
        }
    }
}
