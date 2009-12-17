<?php

/**
 * Joins a specified channel on command from a user.
 */
class Phergie_Plugin_Join extends Phergie_Plugin_Abstract
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
     * Joins a channel.
     *
     * @param string $channels Comma-delimited list of channels to join
     * @param string $keys Optional comma-delimited list of channel keys
     * @return void
     */
    public function onCommandJoin($channels, $keys = null)
    {
        $this->doJoin($channels, $keys);
    }
}
