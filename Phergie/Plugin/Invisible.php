<?php

/**
 * Marks the bot as invisible when it connects to the server.
 *
 * @see http://irchelp.org/irchelp/rfc/chapter4.html#c4_2_3_2
 */
class Phergie_Plugin_Invisible extends Phergie_Plugin_Abstract
{
    /**
     * Marks the bot as invisible when it connects to the server.
     *
     * @return void
     */
    public function onConnect()
    {
        $this->doMode($this->getConnection()->getNick(), '+i');
    }
}
