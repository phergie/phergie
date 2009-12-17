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
    public function fail($message)
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
}
