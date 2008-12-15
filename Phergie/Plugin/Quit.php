<?php

require_once 'Phergie/Plugin/Command.php';

/**
 * Terminates the current connection upon command.
 */
class Phergie_Plugin_Quit extends Phergie_Plugin_Command
{
    /**
     * Issues a quit command when a message is received requesting that the 
     * bot terminate the current connection.
     *
     * @return void
     */
    public function onDoQuit()
    {
        $this->doQuit('Requested by ' . $this->_event->getNick());
    }
}
