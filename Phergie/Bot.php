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
 * @copyright 2008-2010 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie
 */

/**
 * Composite class for other components to represent the bot.
 *
 * @category Phergie 
 * @package  Phergie
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie
 */
class Phergie_Bot
{
    /**
     * Current version of Phergie
     */
    const VERSION = '2.0.1';

    /**
     * Current driver instance
     *
     * @var Phergie_Driver_Abstract
     */
    protected $driver;

    /**
     * Current configuration instance
     *
     * @var Phergie_Config
     */
    protected $config;

    /**
     * Current connection handler instance 
     *
     * @var Phergie_Connection_Handler 
     */
    protected $connections;

    /**
     * Current plugin handler instance
     *
     * @var Phergie_Plugin_Handler
     */
    protected $plugins;

    /**
     * Current event handler instance
     *
     * @var Phergie_Event_Handler
     */
    protected $events;

    /**
     * Current end-user interface instance
     *
     * @var Phergie_Ui_Abstract
     */
    protected $ui;

    /**
     * Current processor instance
     *
     * @var Phergie_Process_Abstract
     */
    protected $processor;

    /**
     * Returns a driver instance, creating one of the default class if 
     * none has been set.
     *
     * @return Phergie_Driver_Abstract
     */
    public function getDriver()
    {
        if (empty($this->driver)) {
            // Check if a driver has been defined in the configuration to use
            // as the default
            $config = $this->getConfig();
            if (isset($config['driver'])) {
                $class = 'Phergie_Driver_' . ucfirst($config['driver']);
            } else {
                // Otherwise default to the Streams driver.
                $class = 'Phergie_Driver_Streams';
            }

            $this->driver = new $class;
        }
        return $this->driver;
    }

    /**
     * Sets the driver instance to use.
     *
     * @param Phergie_Driver_Abstract $driver Driver instance
     *
     * @return Phergie_Bot Provides a fluent interface
     */
    public function setDriver(Phergie_Driver_Abstract $driver)
    {
        $this->driver = $driver;
        return $this;
    }

    /**
     * Sets the configuration to use.
     *
     * @param Phergie_Config $config Configuration instance
     *
     * @return Phergie_Runner_Abstract Provides a fluent interface
     */
    public function setConfig(Phergie_Config $config)
    {
        $this->config = $config;
        return $this;
    }

    /**
     * Returns the entire configuration in use or the value of a specific 
     * configuration setting.
     *
     * @param string $index   Optional index of a specific configuration 
     *        setting for which the corresponding value should be returned
     * @param mixed  $default Value to return if no match is found for $index
     *
     * @return mixed Value corresponding to $index or the entire 
     *         configuration if $index is not specified
     */
    public function getConfig($index = null, $default = null)
    {
        if (empty($this->config)) {
            $this->config = new Phergie_Config;
            $this->config->read('Settings.php');
        }
        if ($index !== null) {
            if (isset($this->config[$index])) {
                return $this->config[$index];
            } else {
                return $default;
            }
        }
        return $this->config;
    }

    /**
     * Returns a plugin handler instance, creating it if it does not already
     * exist and using a default class if none has been set.
     *
     * @return Phergie_Plugin_Handler
     */
    public function getPluginHandler()
    {
        if (empty($this->plugins)) {
            $this->plugins = new Phergie_Plugin_Handler(
                $this->getConfig(),
                $this->getEventHandler()
            );
        }
        return $this->plugins;
    }

    /**
     * Sets the plugin handler instance to use.
     *
     * @param Phergie_Plugin_Handler $handler Plugin handler instance
     *
     * @return Phergie_Bot Provides a fluent interface
     */
    public function setPluginHandler(Phergie_Plugin_Handler $handler)
    {
        $this->plugins = $handler;
        return $this;
    }

    /**
     * Returns an event handler instance, creating it if it does not already
     * exist and using a default class if none has been set.
     *
     * @return Phergie_Event_Handler
     */
    public function getEventHandler()
    {
        if (empty($this->events)) {
            $this->events = new Phergie_Event_Handler;
        }
        return $this->events;
    }

    /**
     * Sets the event handler instance to use.
     *
     * @param Phergie_Event_Handler $handler Event handler instance
     *
     * @return Phergie_Bot Provides a fluent interface
     */
    public function setEventHandler(Phergie_Event_Handler $handler)
    {
        $this->events = $handler;
        return $this;
    }

    /**
     * Returns a connection handler instance, creating it if it does not 
     * already exist and using a default class if none has been set.
     *
     * @return Phergie_Connection_Handler
     */
    public function getConnectionHandler()
    {
        if (empty($this->connections)) {
            $this->connections = new Phergie_Connection_Handler;
        }
        return $this->connections;
    }

    /**
     * Sets the connection handler instance to use.
     *
     * @param Phergie_Connection_Handler $handler Connection handler instance
     *
     * @return Phergie_Bot Provides a fluent interface
     */
    public function setConnectionHandler(Phergie_Connection_Handler $handler)
    {
        $this->connections = $handler;
        return $this;
    }

    /**
     * Returns an end-user interface instance, creating it if it does not 
     * already exist and using a default class if none has been set.
     *
     * @return Phergie_Ui_Abstract
     */
    public function getUi()
    {
        if (empty($this->ui)) {
            $this->ui = new Phergie_Ui_Console;
        }
        return $this->ui;
    }

    /**
     * Sets the end-user interface instance to use.
     *
     * @param Phergie_Ui_Abstract $ui End-user interface instance
     *
     * @return Phergie_Bot Provides a fluent interface
     */
    public function setUi(Phergie_Ui_Abstract $ui)
    {
        $this->ui = $ui;
        return $this;
    }

    /**
     * Returns a processer instance, creating one if none exists. 
     *
     * @return Phergie_Process_Abstract
     */
    public function getProcessor()
    {
        if (empty($this->processor)) {
            $class = 'Phergie_Process_Standard';
            
            $type = $this->getConfig('processor');
            if (!empty($type)) {
                $class = 'Phergie_Process_' . ucfirst($type);
            }

            $this->processor = new $class(
                $this, 
                $this->getConfig('processor.options', array())
            );
        }
        return $this->processor;
    }

    /**
     * Sets the processer instance to use.
     *
     * @param Phergie_Process_Abstract $processor Processer instance
     *
     * @return Phergie_Bot Provides a fluent interface
     */
    public function setProcessor(Phergie_Process_Abstract $processor)
    {
        $this->processor = $processor;
        return $this;
    }

    /**
     * Loads plugins into the plugin handler.
     *
     * @return void
     */
    protected function loadPlugins()
    {
        $config = $this->getConfig();
        $plugins = $this->getPluginHandler();
        $ui = $this->getUi();
        
        $plugins->setAutoload($config['plugins.autoload']);
        foreach ($config['plugins'] as $name) {
            try {
                $plugin = $plugins->addPlugin($name);
                $ui->onPluginLoad($name);
            } catch (Phergie_Plugin_Exception $e) {
                $ui->onPluginFailure($name, $e->getMessage());
                if (!empty($plugin)) {
                    $plugins->removePlugin($plugin);
                }
            }
        }
    }

    /**
     * Configures and establishes connections to IRC servers.
     *
     * @return void
     */
    protected function loadConnections()
    {
        $config = $this->getConfig();
        $driver = $this->getDriver();
        $connections = $this->getConnectionHandler();
        $plugins = $this->getPluginHandler();
        $ui = $this->getUi();

        foreach ($config['connections'] as $data) {
            $connection = new Phergie_Connection($data);
            $connections->addConnection($connection);

            $ui->onConnect($data['host']);
            $driver->setConnection($connection)->doConnect();
            $plugins->setConnection($connection);
            $plugins->onConnect();
        }
    }

    /**
     * Establishes server connections and initiates an execution loop to 
     * continuously receive and process events.
     *
     * @return Phergie_Bot Provides a fluent interface 
     */
    public function run()
    {
        set_time_limit(0);
        
        $timezone = $this->getConfig('timezone', 'UTC');
        date_default_timezone_set($timezone);

        $ui = $this->getUi();
        $ui->setEnabled($this->getConfig('ui.enabled'));

        $this->loadPlugins();
        $this->loadConnections();

        $processor = $this->getProcessor();

        $connections = $this->getConnectionHandler();
        while (count($connections)) {
            $processor->handleEvents();
        }

        $ui->onShutdown();

        return $this;
    }
}
