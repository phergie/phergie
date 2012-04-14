<?php
/**
 * Phergie
 *
 * PHP version 5
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.
 * It is also available through the world-wide-web at this URL:
 * http://phergie.org/license
 *
 * @category  Phergie
 * @package   Phergie
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2012 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie
 */

/**
 * Base class for plugins to provide event handler stubs and commonly needed
 * functionality.
 *
 * @category Phergie
 * @package  Phergie
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie
 */
abstract class Phergie_Plugin_Abstract
{
    /**
     * Current configuration handler
     *
     * @var Phergie_Config
     */
    protected $config;

    /**
     * Plugin handler used to provide access to other plugins
     *
     * @var Phergie_Plugin_Handler
     */
    protected $plugins;

    /**
     * Current event handler instance for outgoing events
     *
     * @var Phergie_Event_Handler
     */
    protected $events;

    /**
     * Current connection instance
     *
     * @var Phergie_Connection
     */
    protected $connection;

    /**
     * Current incoming event being handled
     *
     * @var Phergie_Event_Request|Phergie_Event_Response
     */
    protected $event;

    /**
     * Plugin short name
     *
     * @var string
     */
    protected $name;

    /**
     * Returns the short name for the plugin based on its class name.
     *
     * @return string
     */
    public function getName()
    {
        if (empty($this->name)) {
            $this->name = substr(strrchr(get_class($this), '_'), 1);
        }
        return $this->name;
    }

    /**
     * Sets the short name for the plugin.
     *
     * @param string $name Plugin short name
     *
     * @return Phergie_Plugin_Abstract Provides a fluent interface
     */
    public function setName($name)
    {
        $this->name = (string) $name;
        return $this;
    }

    /**
     * Indicates that the plugin failed to load due to an unsatisfied
     * runtime requirement, such as a missing dependency.
     *
     * @param string $message Error message to provide more information
     *        about the reason for the failure
     *
     * @return Phergie_Plugin_Abstract Provides a fluent interface
     * @throws Phergie_Plugin_Exception Always
     */
    protected function fail($message)
    {
        throw new Phergie_Plugin_Exception(
            $message,
            Phergie_Plugin_Exception::ERR_REQUIREMENT_UNSATISFIED
        );
    }

    /**
     * Sets the current configuration handler.
     *
     * @param Phergie_Config $config Configuration handler
     *
     * @return Phergie_Plugin_Abstract Provides a fluent interface
     */
    public function setConfig(Phergie_Config $config)
    {
        $this->config = $config;
        return $this;
    }

    /**
     * Returns the current configuration handler or the value of a single
     * setting from it.
     *
     * @param string $name    Optional name of a setting for which the value
     *        should be returned instead of the entire configuration handler
     * @param mixed  $default Optional default value to return if no value
     *        is set for the setting indicated by $name
     *
     * @return Phergie_Config|mixed Configuration handler or value of the
     *         setting specified by $name
     * @throws Phergie_Plugin_Exception No configuration handler has been set
     */
    public function getConfig($name = null, $default = null)
    {
        if (empty($this->config)) {
            throw new Phergie_Plugin_Exception(
                'Configuration handler cannot be accessed before one is set',
                Phergie_Plugin_Exception::ERR_NO_CONFIG_HANDLER
            );
        }
        if (!is_null($name)) {
            if (!isset($this->config[$name])) {
                return $default;
            }
            return $this->config[$name];
        }
        return $this->config;
    }

    /**
     * Sets the current plugin handler.
     *
     * @param Phergie_Plugin_Handler $handler Plugin handler
     *
     * @return Phergie_Plugin_Abstract Provides a fluent interface
     */
    public function setPluginHandler(Phergie_Plugin_Handler $handler)
    {
        $this->plugins = $handler;
        return $this;
    }

    /**
     * Returns the current plugin handler.
     *
     * @return Phergie_Plugin_Handler
     * @throws Phergie_Plugin_Exception No plugin handler has been set
     */
    public function getPluginHandler()
    {
        if (empty($this->plugins)) {
            throw new Phergie_Plugin_Exception(
                'Plugin handler cannot be accessed before one is set',
                Phergie_Plugin_Exception::ERR_NO_PLUGIN_HANDLER
            );
        }
        return $this->plugins;
    }

    /**
     * Sets the current event handler.
     *
     * @param Phergie_Event_Handler $handler Event handler
     *
     * @return Phergie_Plugin_Abstract Provides a fluent interface
     */
    public function setEventHandler(Phergie_Event_Handler $handler)
    {
        $this->events = $handler;
        return $this;
    }

    /**
     * Returns the current event handler.
     *
     * @return Phergie_Event_Handler
     * @throws Phergie_Plugin_Exception No event handler has been set
     */
    public function getEventHandler()
    {
        if (empty($this->events)) {
            throw new Phergie_Plugin_Exception(
                'Event handler cannot be accessed before one is set',
                Phergie_Plugin_Exception::ERR_NO_EVENT_HANDLER
            );
        }
        return $this->events;
    }

    /**
     * Sets the current connection.
     *
     * @param Phergie_Connection $connection Connection
     *
     * @return Phergie_Plugin_Abstract Provides a fluent interface
     */
    public function setConnection(Phergie_Connection $connection)
    {
        $this->connection = $connection;
        return $this;
    }

    /**
     * Returns the current event connection.
     *
     * @return Phergie_Connection
     * @throws Phergie_Plugin_Exception No connection has been set
     */
    public function getConnection()
    {
        if (empty($this->connection)) {
            throw new Phergie_Plugin_Exception(
                'Connection cannot be accessed before one is set',
                Phergie_Plugin_Exception::ERR_NO_CONNECTION
            );
        }
        return $this->connection;
    }

    /**
     * Sets the current incoming event to be handled.
     *
     * @param Phergie_Event_Request|Phergie_Event_Response $event Event
     *
     * @return Phergie_Plugin_Abstract Provides a fluent interface
     */
    public function setEvent($event)
    {
        $this->event = $event;
        return $this;
    }

    /**
     * Returns the current incoming event to be handled.
     *
     * @return Phergie_Event_Request|Phergie_Event_Response
     */
    public function getEvent()
    {
        if (empty($this->event)) {
            throw new Phergie_Plugin_Exception(
                'Event cannot be accessed before one is set',
                Phergie_Plugin_Exception::ERR_NO_EVENT
            );
        }
        return $this->event;
    }

    /**
     * Locates a given data file used by this plugin and returns the path to
     * it. This is currently used mainly for compatibility with PEAR packaging.
     *
     * @param string $filename Name of the file
     * @return string|null File path or NULL if the file cannot be found 
     */
    public function findDataFile($filename)
    {
        $class = get_class($this);

        if (class_exists('PEAR_Config')) {
            $config = new PEAR_Config();
            $dataDir = $config->get('data_dir');
            $path = rtrim($dataDir, '\\/') . '/' . $class . '/' . str_replace('_', '/', $class) . '/' . $filename;
            if (file_exists($path)) {
                return $path;
            }
        }

        $r = new ReflectionClass($class);
        $path = dirname($r->getFilename()) . '/' . $this->getName() . '/' . $filename;
        if (file_exists($path)) {
            return $path;
        }

        return null;
    }

    /**
     * Provides do* methods with signatures identical to those of
     * Phergie_Driver_Abstract but that queue up events to be dispatched
     * later.
     *
     * @param string $name Name of the method called
     * @param array  $args Arguments passed in the call
     *
     * @return mixed
     */
    public function __call($name, array $args)
    {
        $subcmd = substr($name, 0, 2);
        if ($subcmd == 'do') {
            $type = strtolower(substr($name, 2));
            $this->getEventHandler()->addEvent($this, $type, $args);
        } else if ($subcmd != 'on') {
            throw new Phergie_Plugin_Exception(
                'Called invalid method ' . $name . ' in ' . get_class($this),
                Phergie_Plugin_Exception::ERR_INVALID_CALL
            );
        }
    }

    /**
     * Handler for when the plugin is initially loaded - useful for checking
     * runtime dependencies or performing any setup necessary for the plugin
     * to function properly such as initializing a database.
     *
     * @return void
     */
    public function onLoad()
    {
    }

    /**
     * Handler for when the bot initially connects to a server.
     *
     * @return void
     */
    public function onConnect()
    {
    }

    /**
     * Handler for each tick, a single iteration of the continuous loop
     * executed by the bot to receive, handle, and send events - useful for
     * repeated execution of tasks on a time interval.
     *
     * @return void
     */
    public function onTick()
    {
    }

    /**
     * Handler for when any event is received but has not yet been dispatched
     * to the plugin handler method specific to its event type.
     *
     * @return bool|null|void FALSE to short-circuit further event
     *         processing, TRUE or NULL otherwise
     */
    public function preEvent()
    {
    }

    /**
     * Handler for after plugin processing of an event has concluded but
     * before any events triggered in response by plugins are sent to the
     * server - useful for modifying outgoing events before they are sent.
     *
     * @return void
     */
    public function preDispatch()
    {
    }

    /**
     * Handler for after any events triggered by plugins in response to a
     * received event are sent to the server.
     *
     * @return void
     */
    public function postDispatch()
    {
    }

    /**
     * Handler for when the server prompts the client for a nick.
     *
     * @return void
     * @link http://irchelp.org/irchelp/rfc/chapter4.html#c4_1_2
     */
    public function onNick()
    {
    }

    /**
     * Handler for when a user obtains operator privileges.
     *
     * @return void
     * @link http://irchelp.org/irchelp/rfc/chapter4.html#c4_1_5
     */
    public function onOper()
    {
    }

    /**
     * Handler for when the client session is about to be terminated.
     *
     * @return void
     * @link http://irchelp.org/irchelp/rfc/chapter4.html#c4_1_6
     */
    public function onQuit()
    {
    }

    /**
     * Handler for when a user joins a channel.
     *
     * @return void
     * @link http://irchelp.org/irchelp/rfc/chapter4.html#c4_2_1
     */
    public function onJoin()
    {
    }

    /**
     * Handler for when a user leaves a channel.
     *
     * @return void
     * @link http://irchelp.org/irchelp/rfc/chapter4.html#c4_2_2
     */
    public function onPart()
    {
    }

    /**
     * Handler for when a user or channel mode is changed.
     *
     * @return void
     * @link http://irchelp.org/irchelp/rfc/chapter4.html#c4_2_3
     */
    public function onMode()
    {
    }

    /**
     * Handler for when a channel topic is viewed or changed.
     *
     * @return void
     * @link http://irchelp.org/irchelp/rfc/chapter4.html#c4_2_4
     */
    public function onTopic()
    {
    }

    /**
     * Handler for when a message is received from a channel or user.
     *
     * @return void
     * @link http://irchelp.org/irchelp/rfc/chapter4.html#c4_4_1
     */
    public function onPrivmsg()
    {
    }

    /**
     * Handler for when the bot receives a CTCP ACTION request.
     *
     * @return void
     * @link http://www.invlogic.com/irc/ctcp.html#4.4
     */
    public function onAction()
    {
    }

    /**
     * Handler for when a notice is received.
     *
     * @return void
     * @link http://irchelp.org/irchelp/rfc/chapter4.html#c4_4_2
     */
    public function onNotice()
    {
    }

    /**
     * Handler for when a user is kicked from a channel.
     *
     * @return void
     * @link http://irchelp.org/irchelp/rfc/chapter4.html#c4_2_8
     */
    public function onKick()
    {
    }

    /**
     * Handler for when the bot receives a ping event from a server, at
     * which point it is expected to respond with a pong request within
     * a short period else the server may terminate its connection.
     *
     * @return void
     * @link http://irchelp.org/irchelp/rfc/chapter4.html#c4_6_2
     */
    public function onPing()
    {
    }

    /**
     * Handler for when the bot receives a CTCP TIME request.
     *
     * @return void
     * @link http://www.invlogic.com/irc/ctcp.html#4.6
     */
    public function onTime()
    {
    }

    /**
     * Handler for when the bot receives a CTCP VERSION request.
     *
     * @return void
     * @link http://www.invlogic.com/irc/ctcp.html#4.1
     */
    public function onVersion()
    {
    }

    /**
     * Handler for when the bot receives a CTCP PING request.
     *
     * @return void
     * @link http://www.invlogic.com/irc/ctcp.html#4.2
     */
    public function onCtcpPing()
    {
    }

    /**
     * Handler for when the bot receives a CTCP request of an unknown type.
     *
     * @return void
     * @link http://www.invlogic.com/irc/ctcp.html
     */
    public function onCtcp()
    {
    }

    /**
     * Handler for when a reply is received for a CTCP PING request sent by
     * the bot.
     *
     * @return void
     * @link http://www.invlogic.com/irc/ctcp.html#4.2
     */
    public function onPingReply()
    {
    }

    /**
     * Handler for when a reply is received for a CTCP TIME request sent by
     * the bot.
     *
     * @return void
     * @link http://www.invlogic.com/irc/ctcp.html#4.6
     */
    public function onTimeReply()
    {
    }

    /**
     * Handler for when a reply is received for a CTCP VERSION request sent
     * by the bot.
     *
     * @return void
     * @link http://www.invlogic.com/irc/ctcp.html#4.1
     */
    public function onVersionReply()
    {
    }

    /**
     * Handler for when a reply received for a CTCP request of an unknown
     * type.
     *
     * @return void
     * @link http://www.invlogic.com/irc/ctcp.html
     */
    public function onCtcpReply()
    {
    }

    /**
     * Handler for when the bot receives a kill request from a server.
     *
     * @return void
     * @link http://irchelp.org/irchelp/rfc/chapter4.html#c4_6_1
     */
    public function onKill()
    {
    }

    /**
     * Handler for when the bot receives an invitation to join a channel.
     *
     * @return void
     * @link http://irchelp.org/irchelp/rfc/chapter4.html#c4_2_7
     */
    public function onInvite()
    {
    }

    /**
     * Handler for when a server response is received to a command issued by
     * the bot.
     *
     * @return void
     * @link http://irchelp.org/irchelp/rfc/chapter6.html
     */
    public function onResponse()
    {
    }
}
