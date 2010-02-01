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
 * @package   Phergie_Core
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2010 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Core
 */

/**
 * End-user interface that produces console output when running the bot from 
 * a shell.
 *
 * @category Phergie 
 * @package  Phergie_Core
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Core
 */
class Phergie_Ui_Console extends Phergie_Ui_Abstract
{
    /**
     * Flag that toggles all console output
     *
     * @var bool
     */
    protected $enabled;

    /**
     * Format for timestamps included in console output
     *
     * @var string
     * @see http://php.net/date
     */
     protected $format;

    /**
     * Constructor to initialize object properties.
     *
     * @return void
     */
    public function __construct()
    {
        $this->enabled = true;
        $this->format = 'H:i:s';
    }

    /** 
     * Outputs a timestamped line to the console if console output is enabled.
     *
     * @param string $line Line to output
     *
     * @return void
     */
    protected function console($line)
    {
        if ($this->enabled) {
            echo date($this->format), ' ', $line, PHP_EOL;
        }
    }

    /**
     * Returns whether console output is enabled.
     *
     * @return bool TRUE if console output is enabled, FALSE otherwise
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * Sets whether console output is enabled.
     *
     * @param bool $enabled TRUE to enable console output, FALSE otherwise, 
     *        defaults to TRUE
     *
     * @return Phergie_Ui_Console Provides a fluent interface
     */
    public function setEnabled($enabled = true)
    {
        $this->enabled = (bool) $enabled;
        return $this;
    }

    /**
     * Returns the format used for timestamps in console output.
     *
     * @return string
     * @see http://php.net/date
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * Sets the format used for timestamps in console output, overwriting 
     * any previous format used.
     *
     * @param string $format Timestamp format
     *
     * @return Phergie_Ui_Console Provides a fluent interface
     * @see http://php.net/date
     */
    public function setFormat($format)
    {
        $this->format = (string) $format;
        return $this;
    }

    /**
     * Outputs a prompt when a server connection is attempted.
     *
     * @param string $host Server hostname
     *
     * @return void 
     */
    public function onConnect($host)
    {
        $this->console('Connecting to ' . $host);
    }

    /**
     * Outputs a prompt when a plugin is loaded successfully. 
     *
     * @param string $plugin Short name of the plugin
     *
     * @return void 
     */
    public function onPluginLoad($plugin)
    {
        $this->console('Loaded plugin ' . $plugin);
    }

    /**
     * Outputs a prompt when a plugin fails to load.
     *
     * @param string $plugin  Short name of the plugin
     * @param string $message Message describing the reason for the failure
     *
     * @return void 
     */
    public function onPluginFailure($plugin, $message)
    {
        $this->console('Unable to load plugin ' . $plugin . ' - ' . $message);
    }

    /**
     * Outputs a prompt when the bot receives an IRC event. 
     *
     * @param Phergie_Event_Abstract $event      Received event
     * @param Phergie_Connection     $connection Connection on which the 
     *        event was received
     *
     * @return void
     */
    public function onEvent(Phergie_Event_Abstract $event, 
        Phergie_Connection $connection
    ) {
        $host = $connection->getHostmask()->getHost();
        $this->console($host . ' <- ' . $event->getRawData());
    }

    /**
     * Outputs a prompt when the bot sends a command to a server.
     *
     * @param Phergie_Event_Command $event      Event representing the 
     *        command being sent
     * @param Phergie_Connection    $connection Connection on which the 
     *        command is being sent 
     *
     * @return void
     */
    public function onCommand(Phergie_Event_Command $event, 
        Phergie_Connection $connection
    ) {
        $plugin = $event->getPlugin()->getName();
        $host = $connection->getHostmask()->getHost();
        $type = strtoupper($event->getType());
        $args = implode(' ', $event->getArguments());
        $this->console(
            $plugin . ' plugin: ' . 
            $host . ' -> ' . $type . ' ' . $args
        ); 
    }

    /**
     * Outputs a prompt when the bot terminates a connection to a server.
     *
     * @param Phergie_Connection $connection Terminated connection 
     *
     * @return void
     */
    public function onQuit(Phergie_Connection $connection)
    {
        $host = $connection->getHostmask()->getHost();
        $this->console('Disconnecting from ' . $host);
    }

    /**
     * Outputs a prompt when the bot shuts down after terminating all server 
     * connections.
     *
     * @return void
     */
    public function onShutdown()
    {
        $this->console('Shutting down');
    }
}
