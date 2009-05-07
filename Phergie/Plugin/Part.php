<?php

/**
 * Parts a specified channel on command from a user.
 */
class Phergie_Plugin_Part extends Phergie_Plugin_Command
{
    /**
     * Parts a channel.
     *
     * @param string $channels Comma-delimited list of channels to leave
     * @return void
     */
    public function onDoPart($channels)
    {
        $this->doPart($channels);
    }
}
