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
 * Base class for obtaining and processing incoming events.
 *
 * @category Phergie 
 * @package  Phergie
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie
 */
abstract class Phergie_Process_Abstract
{

    /**
     * Current driver instance
     *
     * @var Phergie_Driver_Abstract
     */
    protected $driver;

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
     * List of agruments for use within the instance
     *
     * @var array
     */
    protected $options = array();

    /**
     * Gets the required class refences from Phergie_Bot.
     *
     * @param Phergie_Bot $bot     Main bot class
     * @param array       $options Optional processor arguments
     *      
     * @return void
     */
    public function __construct(Phergie_Bot $bot, $options = array())
    {
        $this->driver      = $bot->getDriver();
        $this->plugins     = $bot->getPluginHandler();
        $this->connections = $bot->getConnectionHandler();
        $this->events      = $bot->getEventHandler();
        $this->ui          = $bot->getUi();
        $this->options     = $options;
    }

    /**
     * Sends resulting outgoing events from ealier processing in handleEvents.
     *
     * @param Phergie_Connection $connection Active connection
     *
     * @return void
     */
    protected function processEvents(Phergie_Connection $connection)
    {
        if (count($this->events)) {
            $this->plugins->preDispatch();
            foreach ($this->events as $event) {
                $this->ui->onCommand($event, $connection);

                $method = 'do' . ucfirst(strtolower($event->getType()));
                call_user_func_array(
                    array($this->driver, $method),
                    $event->getArguments()
                );
            }
            $this->plugins->postDispatch();

            if ($this->events->hasEventOfType(Phergie_Event_Request::TYPE_QUIT)) {
                $this->ui->onQuit($connection);
                $this->connections->removeConnection($connection);
            }

            $this->events->clearEvents();
        }
    }

    /**
     * Obtains and processes incoming events.
     *
     * @return void
     */
    public abstract function handleEvents();

}
