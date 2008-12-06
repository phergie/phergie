<?php

require_once 'Phergie/Connection.php';
require_once 'Phergie/Driver/Abstract.php';
require_once 'Phergie/Plugin/Loader.php';

/**
 * Composite class for other components to represent the bot.
 */
class Phergie_Bot
{
    /**
     * Current driver instance
     *
     * @var Phergie_Driver_Abstract
     */
    private $_driver;

    /**
     * List of open connections
     *
     * @var array
     */
    private $_connections = array();

    /**
     * Current plugin loader instance
     *
     * @var Phergie_Plugin_Loader
     */
    private $_plugin;

    /**
     * Flag to enable debugging output
     *
     * @var bool
     */
    private $_debug = false;

    /**
     * Supporting method to handle debugging output based on whether or not 
     * the debugging flag is enabled.
     *
     * @param string $message Debugging message to output
     * @return void
     */
    private function _debug($message)
    {
        if ($this->_debug) {
            echo 'bot: ', $message, PHP_EOL;
        }
    }

    /**
     * Sets a flag to toggle debugging output.
     *
     * @param bool $flag TRUE to enable debugging output (default), FALSE 
     *        otherwise
     * @return Phergie_Driver_Abstract Provides a fluent interface
     */
    public function setDebug($flag = true)
    {
        $this->_debug = $flag;

        return $this;
    }

    /**
     * Returns a driver instance, creating it if it does not already exist
     * and using a default class if none has been set.
     *
     * @return Phergie_Driver_Abstract
     */
    public function getDriver()
    {
        if (empty($this->_driver)) {
            require_once 'Phergie/Driver/Streams.php';
            $this->_driver = new Phergie_Driver_Streams();
        }

        return $this->_driver;
    }

    /**
     * Sets the driver instance to use.
     *
     * @param Phergie_Driver_Abstract $driver
     * @return Phergie_Bot Provides a fluent interface
     */
    public function setDriver(Phergie_Driver_Abstract $driver)
    {
        $this->_driver = $driver;

        return $this;
    }

    /**
     * Returns a plugin loader instance, creating it if it does not already
     * exist and using a default class if none has been set.
     *
     * @return Phergie_Plugin_Loader
     */
    public function getPluginLoader()
    {
        if (empty($this->_plugin)) {
            $this->_plugin = new Phergie_Plugin_Loader();
        }

        return $this->_plugin;
    }

    /**
     * Sets the plugin loader instance to use.
     *
     * @param Phergie_Plugin_Loader $loader
     * @return Phergie_Bot Provides a fluent interface
     */
    public function setPluginLoader(Phergie_Plugin_Loader $loader)
    {
        $this->_plugin = $loader;

        return $this;
    }

    /**
     * Adds a connection to the connection list.
     *
     * @param Phergie_Connection $connection
     * @return Phergie_Bot Provides a fluent interface
     */
    public function addConnection(Phergie_Connection $connection)
    {
        $this->_connections[$connection->getHostmask()] = $connection;

        return $this;
    }

    /**
     * Removes a connection from the connection list.
     *
     * @param Phergie_Connection|string $connection Instance or hostmask for
     *        the connection to remove
     * @return Phergie_Bot Provides a fluent interface
     */
    public function removeConnection($connection)
    {
        if ($connection instanceof Phergie_Connection) {
            $hostmask = array_search($connection, $this->_connections);
        } elseif (is_string($connection)) {
            $hostmask = $connection;
        } else {
            trigger_error('A connection instance or hostmask string must be specified when removing a connection', E_USER_ERROR);
        }

        unset($this->_connections[$hostmask]);
    }

    /**
     * Establishes a connection to the server and initiates an execution
     * loop to continuously receive and process events.
     *
     * @return void
     */
    public function run()
    {
        // Allow the bot to run indefinitely
        set_time_limit(0);

        // Get the current driver instance
        $driver = $this->getDriver();

        // Connection to each server and call the appropriate plugin callback
        foreach ($this->_connections as $connection) {
            $this->_debug('doConnect: ' . $connection->getHostmask());

            $driver
                ->setConnection($connection)
                ->doConnect();

            foreach ($this->_plugin as $plugin) {
                $this->_debug('onConnect: ' . $connection->getHostmask() . ' ' . $plugin->getName());

                $plugin
                    ->setConnection($connection)
                    ->onConnect();
            }
        }

        // Loop until a plugin dispatches an event resulting in termination
        while (true) {

            // Before checking for events, run the tick handler for each plugin
            foreach ($this->_plugin as $plugin) {
                $plugin->onTick();
            }

            // For each connection...
            foreach ($this->_connections as $connection) {

                // Initialize a queue for events initiated by plugins
                $events = array();

                // Check for a new event from the server
                $event = $driver
                    ->setConnection($connection)
                    ->getEvent();

                // If an event is received...
                if ($event) {

                    // Use a central handler if the event is a response
                    if ($event instanceof Phergie_Event_Response) {
                        $eventType = 'response';

                    // Use a specific handler if the event is a request
                    } else {
                        $eventType = $event->getType();
                    }
                }

                // For each plugin... 
                foreach ($this->_plugin as $plugin) {

                    // Execute callbacks and handlers if an event was received
                    if ($event) {
                        $plugin->setConnection($connection);
                        $plugin->setEvent($event);
                        $plugin->preEvent();
                        $plugin->{'on' . ucfirst($eventType)}();
                        $plugin->postEvent();
                        $this->_debug('on' . ucfirst($eventType) . ': ' . $plugin->getName() . ' ' . count($plugin->getEvents()));
                    }

                    // Queue any events initiated by the plugin
                    $events = array_merge($events, $plugin->getEvents());
                    $plugin->clearEvents();
                }

                // If no events were queued, move on to the next connection
                if (!$events) {
                    continue;
                }

                // Execute pre-dispatch callback for plugin events 
                foreach ($this->_plugin as $plugin) {
                    $plugin->preDispatch($events);
                    $this->_debug('preDispatch: ' . $plugin->getName() . ' ' . count($events));
                }

                // Dispatch plugin events
                $quit = null;
                foreach ($events as $event) {
                    $this->_debug($event->getType());
                    if (strcasecmp($event->getType(), 'quit') != 0) {
                        call_user_func_array(
                            array($driver, 'do' . $event->getType()),
                            $event->getArguments()
                        );
                    } elseif (empty($quit)) {
                        $quit = $event;
                    }
                }

                // Execute post-dispatch callback for plugin events
                foreach ($this->_plugin as $plugin) {
                    $this->_debug('postDispatch: ' . $plugin->getName());
                    $plugin->postDispatch($events);
                }

                // Terminate the connection if a QUIT request was dispatched
                if ($quit) {
                    call_user_func_array(
                        array($driver, 'doQuit'), 
                        $quit->getArguments()
                    );
                    foreach ($this->_plugin as $plugin) {
                        $this->_debug('onDisconnect: ' . $plugin->getName());
                        $plugin->onDisconnect();
                    }
                    $this->removeConnection($connection);
                }
            }

            // If all connections have been terminated, break out of the loop
            if (!count($this->_connections)) {
                break;
            }
        }
    }
}
