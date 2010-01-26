<?php

/**
 * End-user interface that produces console output when running the bot from 
 * a shell.
 */
class Phergie_Ui_Console extends Phergie_Ui_Abstract
{
    /**
     * Flag that toggles all console output
     *
     * @var bool
     */
    protected $_enabled;

    /**
     * Format for timestamps included in console output
     *
     * @see http://php.net/date
     * @var string
     */
     protected $_format;

    /**
     * Constructor to initialize object properties.
     *
     * @return void
     */
    public function __construct()
    {
        $this->_enabled = true;
        $this->_format = 'H:i:s';
    }

    /** 
     * Outputs a timestamped line to the console if console output is enabled.
     *
     * @param string $line Line to output
     * @return void
     */
    protected function _console($line)
    {
        if ($this->_enabled) {
            echo date($this->_format), ' ', $line, PHP_EOL;
        }
    }

    /**
     * Returns whether console output is enabled.
     *
     * @return bool TRUE if console output is enabled, FALSE otherwise
     */
    public function isEnabled()
    {
        return $this->_enabled;
    }

    /**
     * Sets whether console output is enabled.
     *
     * @param bool $enabled TRUE to enable console output, FALSE otherwise, 
     *        defaults to TRUE
     * @return Phergie_Ui_Console Provides a fluent interface
     */
    public function setEnabled($enabled = true)
    {
        $this->_enabled = (bool) $enabled;
        return $this;
    }

    /**
     * Returns the format used for timestamps in console output.
     *
     * @see http://php.net/date
     * @return string
     */
    public function getFormat()
    {
        return $this->_format;
    }

    /**
     * Sets the format used for timestamps in console output, overwriting 
     * any previous format used.
     *
     * @see http://php.net/date
     * @param string $format Timestamp format
     * @return Phergie_Ui_Console Provides a fluent interface
     */
    public function setFormat($format)
    {
        $this->_format = (string) $format;
        return $this;
    }

    /**
     * Outputs a prompt when a server connection is attempted.
     *
     * @param string $host Server hostname
     * @return void 
     */
    public function onConnect($host)
    {
        $this->_console('Connecting to ' . $host);
    }

    /**
     * Outputs a prompt when a plugin is loaded successfully. 
     *
     * @param string $plugin Short name of the plugin
     * @return void 
     */
    public function onPluginLoad($plugin)
    {
        $this->_console('Loaded plugin ' . $plugin);
    }

    /**
     * Outputs a prompt when a plugin fails to load.
     *
     * @param string $plugin Short name of the plugin
     * @param string $message Message describing the reason for the failure
     * @return void 
     */
    public function onPluginFailure($plugin, $message)
    {
        $this->_console('Unable to load plugin ' . $plugin . ' - ' . $message);
    }
}
