<?php

require_once 'Phergie/Event/Request.php';
require_once 'Phergie/Plugin/Abstract.php';

/**
 * Event originating from a plugin for the bot.
 */
class Phergie_Event_Command extends Phergie_Event_Request
{
    /**
     * Reference to the plugin instance that created the event
     *
     * @var Phergie_Plugin_Abstract
     */
    protected $_plugin;

    /**
     * Stores a reference to the plugin instance that created the event.
     *
     * @param Phergie_Plugin_Abstract $plugin
     * @return Phergie_Event_Command Provides a fluent interface
     */
    public function setPlugin(Phergie_Plugin_Abstract $plugin)
    {
        $this->_plugin = $plugin;
        return $this;
    }

    /**
     * Returns a reference to the plugin instance that created the event.
     *
     * @return Phergie_Plugin_Abstract
     */
    public function getPlugin()
    {
        return $this->_plugin;
    }
}
