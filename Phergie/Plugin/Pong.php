<?php

require_once 'Phergie/Plugin/Abstract.php';

/**
 * Responds to ping requests.
 */
class Phergie_Plugin_Pong extends Phergie_Plugin_Abstract
{
    /**
     * Sends a PONG response for each PING request received by the server. 
     *
     * @return void
     */
    public function onPing()
    {
        $this->doPong($this->_event[0]);
    }
}
