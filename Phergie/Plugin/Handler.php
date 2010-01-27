<?php

/**
 * Handles on-demand loading of, iteration over, and access to plugins.
 */
class Phergie_Plugin_Handler implements IteratorAggregate
{
    /**
     * Current list of plugin instances
     *
     * @var array
     */
    protected $_plugins;

    /**
     * Paths in which to search for plugin class files
     *
     * @var array
     */
    protected $_paths;

    /**
     * Flag indicating whether plugin classes should be instantiated on 
     * demand if they are requested but no instance currently exists
     *
     * @var bool
     */
    protected $_autoload;

    /**
     * Constructor to initialize class properties and add the path for core 
     * plugins.
     *
     * @return void
     */
    public function __construct()
    {
        $this->_plugins = array();
        $this->_paths = array();
        $this->_autoload = false;

        $this->addPath(dirname(__FILE__), 'Phergie_Plugin_');
    }

    /**
     * Adds a path to search for plugin class files. Paths are searched in
     * the reverse order in which they are added.
     *
     * @param string $path Filesystem directory path
     * @param string $prefix Optional class name prefix corresponding to the 
     *        path
     * @return Phergie_Plugin_Handler Provides a fluent interface
     * @throws Phergie_Plugin_Exception
     */
    public function addPath($path, $prefix = '')
    {
        if (!is_readable($path)) {
            throw new Phergie_Plugin_Exception(
                'Path "' . $path . '" does not reference a readable directory',
                Phergie_Plugin_Exception::ERR_DIRECTORY_NOT_READABLE
            );
        }

        $this->_paths[] = array(
            'path' => rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR,
            'prefix' => $prefix
        );

        return $this;
    }

    /**
     * Adds a plugin instance to the handler. 
     *
     * @param string|Phergie_Plugin_Abstract $plugin Short name of the 
     *        plugin class or a plugin object
     * @param array $args Optional array of arguments to pass to the plugin 
     *        constructor if a short name is passed for $plugin
     * @return Phergie_Plugin_Abstract New plugin instance
     */
    public function addPlugin($plugin, array $args = null)
    {
        // If a short plugin name is specified...
        if (is_string($plugin) && !isset($this->_plugins[$plugin])) {

            // Attempt to locate and load the class
            foreach (array_reverse($this->_paths) as $path) {
                $file = $path['path'] . $plugin . '.php';
                if (file_exists($file)) {
                    require $file;
                    $class = $path['prefix'] . $plugin;
                    if (class_exists($class)) {
                        break;
                    }
                    unset($class);
                }
            }

            // If the class can't be found, display an error
            if (!isset($class)) {
                throw new Phergie_Plugin_Exception(
                    'Class file for plugin "' . $plugin . '" cannot be found',
                    Phergie_Plugin_Exception::ERR_CLASS_NOT_FOUND
                );
            }

            // Check to ensure the class is a plugin class 
            if (!is_subclass_of($class, 'Phergie_Plugin_Abstract')) {
                throw new Phergie_Plugin_Exception(
                    'Class for plugin "' . $plugin . '" does not extend Phergie_Plugin_Abstract',
                    Phergie_Plugin_Exception::ERR_INCORRECT_BASE_CLASS
                );
            }

            // Check to ensure the class can be instantiated
            $reflection = new ReflectionClass($class);
            if (!$reflection->isInstantiable()) {
                throw new Phergie_Plugin_Exception(
                    'Class for plugin "' . $plugin . '" cannot be instantiated',
                    Phergie_Plugin_Exception::ERR_CLASS_NOT_INSTANTIABLE
                );
            }

            // If the class is found, instantiate it
            if (!empty($args)) {
                $instance = $reflection->newInstanceArgs($args);
            } else {
                $instance = new $class;
            }

            // Configure and add the instance 
            $instance->setPluginHandler($this);
            $this->_plugins[$plugin] = $instance;
            $plugin = $instance;

        // If a plugin instance is specified...
        } elseif ($plugin instanceof Phergie_Plugin_Abstract) {

            // Add the plugin instance to the list of plugins
            $this->_plugins[$plugin->getName()] = $plugin;
        }

        return $plugin;
    }

    /**
     * Adds multiple plugin instances to the handler.
     *
     * @param array $plugins List of elements where each is of the form 
     *        'ShortPluginName' or array('ShortPluginName', array($arg1, 
     *        ..., $argN))
     * @return Phergie_Plugin_Handler Provides a fluent interface
     */
    public function addPlugins(array $plugins)
    {
        foreach ($plugins as $plugin) {
            if (is_array($plugin)) {
                $this->addPlugin($plugin[0], $plugin[1]);
            } else {
                $this->addPlugin($plugin);
            }
        }

        return $this;
    }

    /**
     * Removes a plugin instance from the handler.
     *
     * @param string|Phergie_Plugin_Abstract $plugin Short name of the 
     *        plugin class or a plugin object
     * @return Phergie_Plugin_Handler Provides a fluent interface
     */
    public function removePlugin($plugin)
    {
        if ($plugin instanceof Phergie_Plugin_Abstract) {
            $plugin = $plugin->getName();
        }

        unset($this->_plugins[$plugin]);

        return $this;
    }

    /**
     * Returns the corresponding instance for a specified plugin, loading it 
     * if it is not already loaded and autoloading is enabled.
     *
     * @param string $name Short name of the plugin class
     * @return Phergie_Plugin_Abstract Plugin instance
     */
    public function getPlugin($name)
    {
        // If the plugin is loaded, return the instance
        if (isset($this->_plugins[$name])) {
            return $this->_plugins[$name];
        }

        // If autoloading is disabled, display an error
        if (!$this->_autoload) {
            throw new Phergie_Plugin_Exception(
                'Plugin "' . $name . '" has been requested, is not loaded, and autoload is disabled',
                Phergie_Plugin_Exception::ERR_PLUGIN_NOT_LOADED
            );
        }

        // If autoloading is enabled, attempt to load the plugin
        $this->addPlugin($name);

        // Return the added plugin
        return $this->_plugins[$name];
    }

    /**
     * Returns the corresponding instances for multiple specified plugins, 
     * loading them if they are not already loaded and autoloading is 
     * enabled.
     *
     * @param array $names List of short names of the plugin classes
     * @return array Associative array mapping plugin class short names to 
     *         corresponding plugin instances
     */
    public function getPlugins(array $names)
    {
        $plugins = array();
        foreach ($names as $name) {
            $plugins[$name] = $this->getPlugin($name);
        }
        return $plugins;
    }

    /**
     * Returns whether or not at least one instance of a specified plugin 
     * class is loaded.
     *
     * @param string $name Short name of the plugin class
     * @return bool TRUE if an instance exists, FALSE otherwise
     */
    public function hasPlugin($name)
    {
        return isset($this->_plugins[$name]);
    }

    /**
     * Sets a flag used to determine whether plugins should be loaded 
     * automatically if they have not been explicitly loaded.
     *
     * @param bool $flag TRUE to have plugins autoload (default), FALSE 
     *        otherwise
     * @return Phergie_Plugin_Handler Provides a fluent interface.
     */
    public function setAutoload($flag = true)
    {
        $this->_autoload = $flag;

        return $this;
    }

    /**
     * Returns the value of a flag used to determine whether plugins should 
     * be loaded automatically if they have not been explicitly loaded.
     *
     * @return bool TRUE if autoloading is enabled, FALSE otherwise
     */
    public function getAutoload()
    {
        return $this->_autoload;
    }

    /**
     * Allows plugin instances to be accessed as properties of the handler. 
     *
     * @param string $name Short name of the plugin
     * @return Phergie_Plugin_Abstract Requested plugin instance
     */
    public function __get($name)
    {
        return $this->getPlugin(ucfirst($name)); 
    }

    /**
     * Returns an iterator for all currently loaded plugin instances.
     *
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->_plugins);
    }

    /**
     * Proxies method calls to all plugins containing the called method. An  
     * individual plugin may short-circuit this process by explicitly 
     * returning false.
     *
     * @param string $name Name of the method called
     * @param array $args Arguments passed in the method call
     * @return Phergie_Plugin_Handler Provides a fluent interface 
     */
    public function __call($name, array $args)
    {
        foreach ($this->_plugins as $plugin) {
            if (call_user_func_array(array($plugin, $name), $args) === false) {
                break;
            }
        }
        return $this;
    }
}
