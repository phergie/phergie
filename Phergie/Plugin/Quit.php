<?php

/**
 * Terminates the current connection upon command.
 */
class Phergie_Plugin_Quit extends Phergie_Plugin_Abstract
{
    /**
     * Checks for dependencies.
     *
     * @return void
     */
    public function onLoad()
    {
        $this->getPluginHandler()->getPlugin('Command');
    }

    /**
     * Issues a quit command when a message is received requesting that the 
     * bot terminate the current connection.
     *
     * @return void
     */
    public function onCommandQuit()
    {
        $this->doQuit('Requested by ' . $this->getEvent()->getNick());
    }
}
