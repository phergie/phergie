<?php

require_once 'Phergie/Config.php';
require_once 'Phergie/Connection.php';
require_once 'Phergie/Event/Interface.php';
require_once 'Phergie/Event/Command.php';
require_once 'Phergie/Plugin/Loader.php';

/**
 * Base class for plugins to provide event handler stubs and commonly needed
 * functionality.
 */
abstract class Phergie_Plugin_Abstract
{
    /**
     * Current configuration handler
     *
     * @var Phergie_Config
     */
    protected $_config;

    /**
     * Currently active connection
     *
     * @var Phergie_Connection
     */
    protected $_connection;

    /**
     * Current event instance being processed
     *
     * @var Phergie_Event_Interface
     */
    protected $_event;

    /**
     * Plugin loader used to provide access to other plugins
     *
     * @var Phergie_Loader
     */
    protected $_plugin;

    /**
     * Queue of events initiated by the plugin in response to the current
     * event being processed
     *
     * @var array
     */
    protected $_events = array();

    /**
     * Returns the short name for the plugin based on its class name.
     *
     * @return string
     */
    public function getName()
    {
        return substr(strrchr(get_class($this), '_'), 1);
    }

    /**
     * Sets the current configuration handler.
     *
     * @param Phergie_Config $config
     * @return Phergie_Plugin_Abstract Provides a fluent interface
     */
    public function setConfig(Phergie_Config $config)
    {
        $this->_config = $config;

        return $this;
    }

    /**
     * Sets the currently active connection for which events are being 
     * processed before any callbacks are issued.
     *
     * @param Phergie_Connection $connection
     * @return Phergie_Plugin_Abstract Provides a fluent interface
     */
    public function setConnection(Phergie_Connection $connection)
    {
        $this->_connection = $connection;

        return $this;
    }

    /**
     * Returns the currently active connection for which events are being 
     * processed.
     *
     * @return Phergie_Connection
     */
    public function getConnection()
    {
        return $this->_connection;
    }

    /**
     * Sets the current event instance being processed before any callbacks
     * are issued.
     *
     * @param Phergie_Event_Interface $event
     * @return Phergie_Plugin_Abstract Provides a fluent interface
     */
    public function setEvent(Phergie_Event_Interface $event)
    {
        $this->_event = $event;

        return $this;
    }

    /**
     * Returns events initialized by the plugin in response to the current
     * event being processed and clears the internal queue reserved to
     * contain those events.
     *
     * @return array
     */
    public function getEvents()
    {
        return $this->_events;
    }

    /**
     * Clears the internal queue reserved for events being initiated by the 
     * plugin.
     *
     * @return Phergie_Plugin_Abstract Provides a fluent interface 
     */
    public function clearEvents()
    {
        $this->_events = array();

        return $this;
    }

    /**
     * Sets the current plugin loader.
     *
     * @param Phergie_Loader $loader
     * @return Phergie_Plugin_Abstract Provides a fluent interface
     */
    public function setPluginLoader(Phergie_Plugin_Loader $loader)
    {
        $this->_plugin = $loader;

        return $this;
    }

    /**
     * Callback dispatched before connections are checked for new events, 
     * allowing for the execution of logic that does not require an event 
     * to occur.
     *
     * @return void
     */
    public function onTick() { }

    /**
     * Callback dispatched right before commands are to be dispatched to the
     * server, allowing plugins to mutate, remove, or reorder events.
     *
     * @param array $events Events to be dispatched
     * @return void
     */
    public function preDispatch(array &$events) { }

    /**
     * Callback dispatched right after commands are dispatched to the server,
     * informing plugins of what events were sent in and in what order.
     *
     * @param array $events Events that were dispatched
     * @return void
     */
    public function postDispatch(array $events) { }

    /**
     * Callback dispatched before a handler is called for the current event
     * based on its type.
     *
     * @return void
     */
    public function preEvent() { }

    /**
     * Callback dispatched after a handle is called for the current event 
     * based on its type.
     *
     * @return void
     */
    public function postEvent() { }

    /**
     * Handler for when the bot connects to the current server.
     *
     * @return void
     */
    public function onConnect() { }

    /**
     * Handler for when the bot disconnects from the current server.
     *
     * @return void
     */
    public function onDisconnect() { }

    /**
     * Handler for when the client session is about to be terminated.
     *
     * @return void
     */
    public function onQuit() { }

    /**
     * Handler for when a user joins a channel.
     *
     * @return void
     */
    public function onJoin() { }

    /**
     * Handler for when a user leaves a channel.
     *
     * @return void
     */
    public function onPart() { }

    /**
     * Handler for when a user sends an invite request.
     *
     * @return void
     */
    public function onInvite() { }

    /**
     * Handler for when a user obtains operator privileges.
     *
     * @return void
     */
    public function onOper() { }

    /**
     * Handler for when a channel topic is viewed or changed.
     *
     * @return void
     */
    public function onTopic() { }

    /**
     * Handler for when a user or channel mode is changed.
     *
     * @return void
     */
    public function onMode() { }

    /**
     * Handler for when the server prompts the client for a nick.
     *
     * @return void
     */
    public function onNick() { }

    /**
     * Handler for when a message is received from a channel or user.
     *
     * @return void
     */
    public function onPrivmsg() { }

    /**
     * Handler for when an action is received from a channel or user
     *
     * @return void
     */
    public function onAction() { }

    /**
     * Handler for when a notice is received.
     *
     * @return void
     */
    public function onNotice() { }

    /**
     * Handler for when a user is kicked from a channel.
     *
     * @return void
     */
    public function onKick() { }

    /**
     * Handler for when the server or a user checks the client connection to
     * ensure activity.
     *
     * @return void
     */
    public function onPing() { }

    /**
     * Handler for when the server sends a CTCP TIME request.
     *
     * @return void
     */
    public function onTime() { }

    /**
     * Handler for when the server sends a CTCP VERSION request.
     *
     * @return void
     */
    public function onVersion() { }

    /**
     * Handler for the reply to a CTCP PING request.
     *
     * @return void
     */
    public function onPingReply() { }

    /**
     * Handler for the reply to a CTCP TIME request.
     *
     * @return void
     */
    public function onTimeReply() { }

    /**
     * Handler for the reply to a CTCP VERSION request. 
     *
     * @return void
     */
    public function onVersionReply() { }

    /**
     * Handler for unrecognized CTCP requests.
     *
     * @return void
     */
    public function onCtcp() { }

    /**
     * Handler for unrecognized CTCP responses.
     *
     * @return void
     */
    public function onCtcpReply() { }

    /**
     * Handler for raw requests from the server.
     *
     * @return void
     */
    public function onRaw() { }

    /**
     * Handler for when the server sends a kill request.
     *
     * @return void
     */
    public function onKill() { }

    /**
     * Handler for when a server response is received to a client-issued
     * command.
     *
     * @return void
     */
    public function onResponse() { }

    /**
     * Provides do* methods with signatures identical to those of
     * Phergie_Driver_Abstract but that queue up events to be dispatched
     * later.
     *
     * @param string $name Name of the method called
     * @param array $args Arguments passed in the call
     * @return mixed
     */
    public function __call($name, array $args)
    {
        if (substr($name, 0, 2) == 'do') {
            $type = substr($name, 2);
            if (defined('Phergie_Event_Command::TYPE_' . strtoupper($type))) {
                $request = new Phergie_Event_Command();
                $request
                    ->setPlugin($this)
                    ->setType($type)
                    ->setArguments($args);
                $this->_events[] = $request;
            }
        }
    }
}
