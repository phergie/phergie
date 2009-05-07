<?php

/**
 * Handles on-demand loading of and access to plugins.
 */
class Phergie_Plugin_Loader implements IteratorAggregate
{
    /**
     * Current list of plugin instances
     *
     * @var array
     */
    private $_plugins = array();

    /**
     * Paths in which to search for plugin class files
     *
     * @var array
     */
    private $_paths = array();

    /**
     * Flag indicating whether plugin classes should be instantiated on 
     * demand if they are requested but no instance currently exists
     *
     * @var bool
     */
    private $_autoload = false;

    /**
     * Adds a path to search for plugin class files. Paths are searched in
     * the reverse order in which they are added.
     *
     * @param string $path Filesystem directory path
     * @param string $prefix Optional class name prefix corresponding to the 
     *        path
     * @return Phergie_Plugin_Loader Provides a fluent interface
     */
    public function addPath($path, $prefix = '')
    {
        if (!is_readable($path)) {
            trigger_error('Path ' . $path . ' does not reference a readable directory', E_USER_ERROR);
        }

        $this->_paths[] = array(
            'path' => rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR,
            'prefix' => $prefix
        );

        return $this;
    }

    /**
     * Adds a plugin instance to the loader.
     *
     * @param string|Phergie_Plugin_Abstract $plugin Short name of the 
     *        plugin class or a plugin object
     * @param array $args Optional array of arguments to pass to the plugin 
     *        constructor if a short name is passed for $plugin
     * @return bool Plugin instance if it was created successfully, FALSE 
     *         otherwise 
     */
    public function addPlugin($plugin, array $args = null)
    {
        // If a short plugin name is specified...
        if (is_string($plugin)) {

            // Check if the plugin was already loaded
            if(isset($this->_plugins[$plugin])) {
                return $this->_plugins[$plugin];
            }

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
                trigger_error('Class for plugin ' . $plugin . ' cannot be found', E_USER_ERROR);
                return false;
            }

            // Check to ensure the class is a plugin class 
            if (!is_subclass_of($class, 'Phergie_Plugin_Abstract')) {
                trigger_error('Class ' . $class . ' does not extend Phergie_Plugin_Abstract', E_USER_ERROR);
                return false;
            }

            // Check to ensure the class can be instantiated
            $reflection = new ReflectionClass($class);
            if (!$reflection->isInstantiable()) {
                trigger_error('Class ' . $class . ' cannot be instantiated', E_USER_ERROR);
                return false;
            }

            // If the class is found, instantiate it
            if (!empty($args)) {
                $instance = $reflection->newInstanceArgs($args);
            } else {
                $instance = new $class();
            }
            $instance->setPluginLoader($this);
            $this->_plugins[$plugin] = $instance;

            // Indicate success
            return $instance; 

        // If a plugin instance is specified...
        } elseif ($plugin instanceof Phergie_Plugin_Abstract) {

            // Add the plugin instance to the list of plugins
            $this->_plugins[$plugin->getName()] = $plugin;

            // Indicate success
            return $plugin;
        }

        // An unknown situation occurred
        return false;
    }

    /**
     * Adds multiple plugin instances to the loader.
     *
     * @param array $plugins List of elements where each is of the form 
     *        'ShortPluginName' or array('ShortPluginName', array($arg1, 
     *        ..., $argN))
     * @return Phergie_Plugin_Loader Provides a fluent interface
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
     * Removes a plugin instance from the loader.
     *
     * @param string|Phergie_Plugin_Abstract $plugin Short name of the 
     *        plugin class or a plugin object
     * @return Phergie_Plugin_Loader Provides a fluent interface
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
            trigger_error('Plugin ' . $name . ' has been requested, is not loaded, and autoload is disabled', E_USER_ERROR);
        }

        // If autoloading is enabled, attempt to load the plugin
        $this->addPlugin($name);

        // Return the plugin if it is successfully loaded
        return $this->_plugins[$name];
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
     * @return Phergie_Plugin_Loader Provides a fluent interface.
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
     * Allows plugin instances to be accessed as properties of the loader. 
     * Returns the first instance of the requested plugin that was added if 
     * it exists and adds an instance if none exists.
     *
     * @param string $name Short name of the plugin
     * @return Phergie_Plugin_Abstract|null Plugin instance if it exists or 
     *         NULL if it does not
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
}
