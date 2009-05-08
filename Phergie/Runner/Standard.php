<?php

/**
 * Standard runner.
 */
class Phergie_Runner_Standard extends Phergie_Runner_Abstract
{
    /**
     * Validates connection data in the configuration. 
     *
     * @return void
     */
    protected function _validateConnections()
    {
        if (empty($this->_config['connections']) || !is_array($this->_config['connections'])) {
            trigger_error('The setting \'connections\' is required and must be an array', E_USER_ERROR);
        }

        $required = array('hostname', 'username', 'realname', 'nick');
        foreach ($this->_config['connections'] as $settings) {
            if (!is_array($settings)) {
                trigger_error('Each item in setting \'connections\' must be an array', E_USER_ERROR);
            }
            if (array_intersect(array_keys($settings), $required) != $required) {
                trigger_error('Each item in setting \'connections\' must have the following keys: ' . implode(', ', $required), E_USER_ERROR);
            }
        }
    }

    /**
     * Validates plugin data in the configuration.
     *
     * @return void
     */
    protected function _validatePlugins()
    {
        // Validate the plugin list 
        if (empty($this->_config['plugins'])) {
            trigger_error('The \'plugins\' setting must contain an array of one or more short plugin names', E_USER_ERROR);
        }
    }

    /**
     * Configures the plugin loader.
     *
     * @return void
     */
    protected function _configurePluginLoader()
    {
        if (isset($this->_config['plugins.autoload'])) {
            $this->_plugin->setAutoload($this->_config['plugins.autoload']);
        }
    }

    /**
     * Loads plugins using the plugin loader as per the configuration.
     *
     * @return void
     */
    protected function _loadPlugins()
    {
        $remove = array();
        foreach ($this->_config['plugins'] as $pluginName) {
            if ($plugin = $this->_plugin->addPlugin($pluginName)) {
                $plugin->setConfig($this->_config);
                if($error = $plugin->checkDependencies()) {
                    $remove[] = $plugin;
                    trigger_error(get_class($plugin) . ': ' . $error, E_USER_WARNING);
                }
             }
         }

        foreach($remove as $plugin) {
            $loader->removePlugin($plugin);
        }
    }

    /**
     * Configures the bot.
     *
     * @return void
     */
    protected function _configureBot()
    {
        $this->_bot->setPluginLoader($this->_plugin);

        foreach ($this->_config['connections'] as $settings) {
            $this->_bot->addConnection(new Phergie_Connection($settings));
        }
    }

    /**
     * Implements Phergie_Runner_Abstract::run().
     *
     * @return Phergie_Runner_Standard Provides a fluent interface
     */
    public function run()
    {
        $this->getConfig();
        $this->getPluginLoader();
        $this->getBot();

        $this->_validateConnections();
        $this->_validatePlugins();
        $this->_configurePluginLoader();
        $this->_loadPlugins();
        $this->_configureBot();

        $this->_bot->run();
    }
}
