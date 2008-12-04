<?php

require_once 'Phergie/Plugin/Abstract.php';

/**
 * Terminates the current connection upon command.
 */
class Phergie_Plugin_Quit extends Phergie_Plugin_Abstract
{
    /**
     * Issues a quit command when a message is received requesting that the 
     * bot terminate the current connection.
     *
     * @return void
     */
    public function onPrivmsg()
    {
        if ($this->_event->getText() == $this->_connection->getNick() . ': quit') {
            $this->doQuit('Requested by ' . $this->_event->getNick());
        }
    }
}
