<?php

/**
 * Base class for end-user interfaces.
 */
abstract class Phergie_Ui_Abstract
{
    /**
     * Event fired when a server connection is attempted.
     *
     * @param string $host Server hostname
     * @return void 
     */
    public function onConnect($host)
    {
    }

    /**
     * Event fired when an attempt is made to load a plugin.
     *
     * @param string $plugin Short name of the plugin
     * @return void 
     */
    public function onPluginLoad($plugin)
    {
    }

    /**
     * Event fired when a plugin fails to load.
     *
     * @param string $plugin Short name of the plugin
     * @param string $message Message describing the reason for the failure
     * @return void 
     */
    public function onPluginFailure($plugin, $message)
    {
    }
}
