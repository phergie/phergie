<?php

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
     * Plugin handler used to provide access to other plugins
     *
     * @var Phergie_Plugin_Handler
     */
    protected $_plugins;

    /**
     * Current event handler instance for outgoing events
     *
     * @var Phergie_Event_Handler
     */
    protected $_events;

    /**
     * Current connection instance
     *
     * @var Phergie_Connection
     */
    protected $_connection;

    /**
     * Current incoming event being handled
     *
     * @var Phergie_Event_Request|Phergie_Event_Response
     */
    protected $_event;

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
     * Indicates that the plugin failed to load due to an unsatisfied 
     * runtime requirement, such as a missing dependency.
     *
     * @param string $message Error message to provide more information 
     *        about the reason for the failure
     * @throws Phergie_Plugin_Exception Always
     * @return Phergie_Plugin_Abstract Provides a fluent interface
     */
    protected function _fail($message)
    {
        throw new Phergie_Plugin_Exception(
            $message,
            Phergie_Plugin_Exception::ERR_REQUIREMENT_UNSATISFIED
        );
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
     * Returns the current configuration handler.
     *
     * @throws Phergie_Plugin_Exception No configuration handler has been set 
     * @return Phergie_Config Configuration handler
     */
    public function getConfig()
    {
        if (empty($this->_config)) {
            throw new Phergie_Plugin_Exception(
                'Configuration handler cannot be accessed before one is set',
                Phergie_Plugin_Exception::ERR_NO_CONFIG_HANDLER
            );
        }
        return $this->_config;
    }

    /**
     * Sets the current plugin handler.
     *
     * @param Phergie_Plugin_Handler $handler
     * @return Phergie_Plugin_Abstract Provides a fluent interface
     */
    public function setPluginHandler(Phergie_Plugin_Handler $handler)
    {
        $this->_plugins = $handler;
        return $this;
    }

    /**
     * Returns the current plugin handler.
     *
     * @throws Phergie_Plugin_Exception No plugin handler has been set 
     * @return Phergie_Plugin_Handler
     */
    public function getPluginHandler()
    {
        if (empty($this->_plugins)) {
            throw new Phergie_Plugin_Exception(
                'Plugin handler cannot be accessed before one is set',
                Phergie_Plugin_Exception::ERR_NO_PLUGIN_HANDLER
            );
        }
        return $this->_plugins;
    }

    /**
     * Sets the current event handler.
     *
     * @param Phergie_Event_Handler $handler
     * @return Phergie_Plugin_Abstract Provides a fluent interface
     */
    public function setEventHandler(Phergie_Event_Handler $handler)
    {
        $this->_events = $handler;
        return $this;
    }

    /**
     * Returns the current event handler.
     *
     * @throws Phergie_Plugin_Exception No event handler has been set 
     * @return Phergie_Event_Handler
     */
    public function getEventHandler()
    {
        if (empty($this->_events)) {
            throw new Phergie_Plugin_Exception(
                'Event handler cannot be accessed before one is set',
                Phergie_Plugin_Exception::ERR_NO_EVENT_HANDLER
            );
        }
        return $this->_events;
    }

    /**
     * Sets the current connection.
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
     * Returns the current event connection.
     *
     * @throws Phergie_Plugin_Exception No connection has been set 
     * @return Phergie_Connection
     */
    public function getConnection()
    {
        if (empty($this->_connection)) {
            throw new Phergie_Plugin_Exception(
                'Connection cannot be accessed before one is set',
                Phergie_Plugin_Exception::ERR_NO_CONNECTION
            );
        }
        return $this->_connection;
    }

    /**
     * Sets the current incoming event to be handled.
     *
     * @param Phergie_Event_Request|Phergie_Event_Response $event
     * @return Phergie_Plugin_Abstract Provides a fluent interface
     */
    public function setEvent($event)
    {
        $this->_event = $event;
        return $this;
    }

    /**
     * Returns the current incoming event to be handled.
     *
     * @return Phergie_Event_Request|Phergie_Event_Response
     */
    public function getEvent()
    {
        if (empty($this->_connection)) {
            throw new Phergie_Plugin_Exception(
                'Event cannot be accessed before one is set',
                Phergie_Plugin_Exception::ERR_NO_EVENT
            );
        }
        return $this->_event;
    }

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
            $type = strtolower(substr($name, 2));
            $this->getEventHandler()->addEvent($this, $type, $args);
       }
    }

    /**
     * Handler for when the plugin is initially loaded - useful for checking 
     * runtime dependencies or performing any setup necessary for the plugin 
     * to function properly such as initializing a database.
     *
     * @return void
     */
    public function onLoad() { }

    /**
     * Handler for when the bot initially connects to a server.
     *
     * @return void
     */
    public function onConnect() { }

    /**
     * Handler for each tick, a single iteration of the continuous loop 
     * executed by the bot to receive, handle, and send events - useful for  
     * repeated execution of tasks on a time interval.
     *
     * @return void
     */
    public function onTick() { }

    /**
     * Handler for when the server prompts the client for a nick.
     *
     * @see http://irchelp.org/irchelp/rfc/chapter4.html#c4_1_2
     * @return void
     */
    public function onNick() { }

    /**
     * Handler for when a user obtains operator privileges.
     *
     * @see http://irchelp.org/irchelp/rfc/chapter4.html#c4_1_5
     * @return void
     */
    public function onOper() { }

    /**
     * Handler for when the client session is about to be terminated.
     *
     * @see http://irchelp.org/irchelp/rfc/chapter4.html#c4_1_6
     * @return void
     */
    public function onQuit() { }

    /**
     * Handler for when a user joins a channel.
     *
     * @see http://irchelp.org/irchelp/rfc/chapter4.html#c4_2_1
     * @return void
     */
    public function onJoin() { }

    /**
     * Handler for when a user leaves a channel.
     *
     * @see http://irchelp.org/irchelp/rfc/chapter4.html#c4_2_2
     * @return void
     */
    public function onPart() { }

    /**
     * Handler for when a user or channel mode is changed.
     *
     * @see http://irchelp.org/irchelp/rfc/chapter4.html#c4_2_3
     * @return void
     */
    public function onMode() { }

    /**
     * Handler for when a channel topic is viewed or changed.
     *
     * @see http://irchelp.org/irchelp/rfc/chapter4.html#c4_2_4
     * @return void
     */
    public function onTopic() { }

    /**
     * Handler for when a message is received from a channel or user.
     *
     * @see http://irchelp.org/irchelp/rfc/chapter4.html#c4_4_1
     * @return void
     */
    public function onPrivmsg() { }

    /**
     * Handler for when the bot receives a CTCP ACTION request.
     *
     * @see http://www.invlogic.com/irc/ctcp.html#4.4
     * @return void
     */
    public function onAction() { }

    /**
     * Handler for when a notice is received.
     *
     * @see http://irchelp.org/irchelp/rfc/chapter4.html#c4_4_2
     * @return void
     */
    public function onNotice() { }

    /**
     * Handler for when a user is kicked from a channel.
     *
     * @see http://irchelp.org/irchelp/rfc/chapter4.html#c4_2_8
     * @return void
     */
    public function onKick() { }

    /**
     * Handler for when the bot receives a ping event from a server, at 
     * which point it is expected to respond with a pong request within 
     * a short period else the server may terminate its connection.
     *
     * @see http://irchelp.org/irchelp/rfc/chapter4.html#c4_6_2 
     * @return void
     */
    public function onPing() { }

    /**
     * Handler for when the bot receives a CTCP TIME request.
     *
     * @see http://www.invlogic.com/irc/ctcp.html#4.6
     * @return void
     */
    public function onTime() { }

    /**
     * Handler for when the bot receives a CTCP VERSION request.
     *
     * @see http://www.invlogic.com/irc/ctcp.html#4.1
     * @return void
     */
    public function onVersion() { }

    /**
     * Handler for when the bot receives a CTCP request of an unknown type. 
     *
     * @see http://www.invlogic.com/irc/ctcp.html
     * @return void
     */
    public function onCtcp() { }

    /**
     * Handler for when a reply is received for a CTCP PING request sent by 
     * the bot.
     *
     * @see http://www.invlogic.com/irc/ctcp.html#4.2
     * @return void
     */
    public function onPingReply() { }

    /**
     * Handler for when a reply is received for a CTCP TIME request sent by 
     * the bot.
     *
     * @see http://www.invlogic.com/irc/ctcp.html#4.6
     * @return void
     */
    public function onTimeReply() { }

    /**
     * Handler for when a reply is received for a CTCP VERSION request sent 
     * by the bot.
     *
     * @see http://www.invlogic.com/irc/ctcp.html#4.1
     * @return void
     */
    public function onVersionReply() { }

    /**
     * Handler for when a reply received for a CTCP request of an unknown 
     * type.
     *
     * @see http://www.invlogic.com/irc/ctcp.html
     * @return void
     */
    public function onCtcpReply() { }

    /**
     * Handler for when the bot receives a kill request from a server.
     *
     * @see http://irchelp.org/irchelp/rfc/chapter4.html#c4_6_1
     * @return void
     */
    public function onKill() { }

    /**
     * Handler for when the bot receives an invitation to join a channel. 
     *
     * @see http://irchelp.org/irchelp/rfc/chapter4.html#c4_2_7
     * @return void
     */
    public function onInvite() { }

    /**
     * Handler for when a server response is received to a command issued by 
     * the bot.
     *
     * @see http://irchelp.org/irchelp/rfc/chapter6.html
     * @return void
     */
    public function onResponse() { }
}
