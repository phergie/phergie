<?php

require_once 'Phergie/Plugin/Abstract.php';

/**
 * Uses a self CTCP PING to ensure that the client connection has not been 
 * dropped.
 */
class Phergie_Plugin_Ping extends Phergie_Plugin_Abstract
{
    /**
     * Timestamp for the last instance in which an event was received
     *
     * @var int
     */
    private $_lastEvent;

    /**
     * Timestamp for the last instance in which a PING was sent
     *
     * @var int
     */
    private $_lastPing;

    /**
     * Initialize event timestamps upon connecting to the server.
     *
     * @return void
     */
    public function onConnect()
    {
        $this->_lastEvent = time();
        $this->_lastPing = null;
    }

    /**
     * Updates the timestamp since the last received event when a new event 
     * arrives.
     *
     * @return void
     */
    public function preEvent()
    {
        $this->_lastEvent = time();
    }

    /**
     * Clears the ping time if a reply is received.
     *
     * @return void
     */
    public function onPingReply()
    {
        $this->_lastPing = null;
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
        
        if (!empty($this->_lastPing) 
            && $time - $this->_lastPing > $this->_config['ping.ping']) {
            $this->doQuit();
        } elseif ($time - $this->_lastEvent > $this->_config['ping.event']) {
            $this->_lastPing = time();
            $this->doPing($this->_connection->getNick());
        }
    }
}
