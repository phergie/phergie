<?php

/**
 * Base class for runners, which handle initialization and configuration of 
 * resources to run a bot.
 */
abstract class Phergie_Runner_Abstract
{
    /**
     * Configuration
     *
     * @var Phergie_Config
     */
    protected $_config;

    /**
     * Plugin loader
     *
     * @var Phergie_Plugin_Loader
     */
    protected $_plugin;

    /**
     * Bot
     *
     * @var Phergie_Bot
     */
    protected $_bot;

    /**
     * Sets the configuration to use.
     *
     * @param Phergie_Config $config
     * @return Phergie_Runner_Abstract Provides a fluent interface
     */
    public function setConfig(Phergie_Config $config)
    {
        $this->_config = $config;
        return $this;
    }

    /**
     * Returns the configuration in use.
     *
     * @return Phergie_Config
     */
    public function getConfig()
    {
        if (empty($this->_config)) {
            if (!is_executable('Settings.php')) {
                trigger_error('No configuration has been set', E_USER_ERROR);
            }
            $this->_config = new Phergie_Config;
            $this->_config->read('Settings.php');
        }
        return $this->_config;
    }

    /**
     * Sets the plugin loader to use.
     *
     * @param Phergie_Plugin_Loader $loader
     * @return Phergie_Runner_Abstract Provides a fluent interface
     */
    public function setPluginLoader(Phergie_Plugin_Loader $loader)
    {
        $this->_plugin = $loader;
        return $this;
    }

    /**
     * Returns the plugin loader in use.
     *
     * @return Phergie_Plugin_Loader
     */
    public function getPluginLoader()
    {
        if (empty($this->_plugin)) {
            $this->_plugin = new Phergie_Plugin_Loader;
            $this->_plugin->addPath('Plugin', 'Phergie_Plugin_');
        }
        return $this->_plugin;
    }

    /**
     * Sets the bot to use.
     *
     * @param Phergie_Bot $bot
     * @return Phergie_Runner_Abstract Provides a fluent interface
     */
    public function setBot(Phergie_Bot $bot)
    {
        $this->_bot = $bot;
        return $this;
    }

    /**
     * Returns the bot in use.
     *
     * @return Phergie_Bot
     */
    public function getBot()
    {
        if (empty($this->_bot)) {
            $this->_bot = new Phergie_Bot;
        }
        return $this->_bot;
    }

    /**
     * Configures all necessary resources and executes the bot run process.
     *
     * @return Phergie_Runner_Abstract Provides a fluent interface
     */
    abstract public function run();
}
