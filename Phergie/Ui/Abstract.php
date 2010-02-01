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
 * Base class for end-user interfaces.
 *
 * @category Phergie 
 * @package  Phergie_Core
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Core
 */
abstract class Phergie_Ui_Abstract
{
    /**
     * Handler for when a server connection is attempted.
     *
     * @param string $host Server hostname
     *
     * @return void 
     */
    public function onConnect($host)
    {
    }

    /**
     * Handler for when an attempt is made to load a plugin.
     *
     * @param string $plugin Short name of the plugin
     *
     * @return void 
     */
    public function onPluginLoad($plugin)
    {
    }

    /**
     * Handler for when a plugin fails to load.
     *
     * @param string $plugin  Short name of the plugin
     * @param string $message Message describing the reason for the failure
     *
     * @return void 
     */
    public function onPluginFailure($plugin, $message)
    {
    }

    /**
     * Handler for when the bot receives an IRC event. 
     *
     * @param Phergie_Event_Abstract $event      Received event
     * @param Phergie_Connection     $connection Connection on which the event 
     *        was received
     *
     * @return void
     */
    public function onEvent(Phergie_Event_Abstract $event, 
        Phergie_Connection $connection
    ) {
    }

    /**
     * Handler for when the bot sends a command to a server.
     *
     * @param Phergie_Event_Command $event      Event representing the command 
     *        being sent
     * @param Phergie_Connection    $connection Connection on which the command  
     *        is being sent 
     *
     * @return void
     */
    public function onCommand(Phergie_Event_Command $event, 
        Phergie_Connection $connection
    ) {
    }

    /**
     * Handler for when the bot terminates a connection to a server.
     *
     * @param Phergie_Connection $connection Terminated connection 
     *
     * @return void
     */
    public function onQuit(Phergie_Connection $connection)
    {
    }

    /**
     * Handler for when the bot shuts down after terminating all server 
     * connections.
     *
     * @return void
     */
    public function onShutdown()
    {
    }
}
