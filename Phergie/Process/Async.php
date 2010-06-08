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
 * Connection data processor which polls to handle input in an 
 * asynchronous manner. Will also cause the application tick at
 * the user-defined wait time.
 *
 * @category Phergie 
 * @package  Phergie
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie
 */
class Phergie_Process_Async extends Phergie_Process_Abstract
{
    /**
     * Length of time to poll for stream activity (seconds)
     *
     * @var int
     */
    protected $sec;

    /**
     * Length of time to poll for stream activity (microseconds)
     *
     * @var int
     */
    protected $usec;

    /**
     * Records when the application last performed a tick
     *
     * @var int
     */
    protected $lastTick = 0;

    /**
     * Overrides the parent class to set the poll time. 
     *
     * @param Phergie_Bot $bot     Main bot class
     * @param array       $options Processor arguments
     *
     * @return void
     */
    public function __construct(Phergie_Bot $bot, array $options)
    {
        if (!$bot->getDriver() instanceof Phergie_Driver_Streams) {
            throw new Phergie_Process_Exception(
                'The Async event processor requires the Streams driver'
            );
        }

        foreach (array('sec', 'usec') as $var) {
            if (!isset($options[$var]) xor !is_int($options[$var])) {
                throw new Phergie_Process_Exception(
                    'Processor option "' . $var . '" must be an integer'
                );
            }
            $this->$var = $options[$var];
        }

        if (empty($this->sec) && empty($this->usec)) {
            throw new Phergie_Process_Exception(
                'One of the processor options "sec" or "usec" must be specified'
            );
        }

        parent::__construct($bot, $options);
    }

    /**
     * Waits for stream activity and performs event processing on 
     * connections with data to read.
     *
     * @return void
     */
    protected function handleEventsAsync()
    {
        $hostmasks = $this->driver->getActiveReadSockets($this->sec, $this->usec);
        if (!$hostmasks) {
            return;
        }
        $connections = $this->connections->getConnections($hostmasks);
        foreach ($connections as $connection) {
            $this->driver->setConnection($connection);
            $this->plugins->setConnection($connection);
            $this->plugins->onTick();

            if ($event = $this->driver->getEvent()) {
                $this->ui->onEvent($event, $connection);
                $this->plugins->setEvent($event);

                if (!$this->plugins->preEvent()) {
                    continue;
                }

                $this->plugins->{'on' . ucfirst($event->getType())}();
            }

            $this->processEvents($connection);
        }
    }

    /**
     * Perform application tick event on all plugins and connections.
     *
     * @return void
     */
    protected function doTick()
    {
        foreach ($this->connections as $connection) {
            $this->plugins->setConnection($connection);
            $this->plugins->onTick();
            $this->processEvents($connection);
        }
    }

    /**
     * Obtains and processes incoming events, then sends resulting outgoing 
     * events.
     *
     * @return void
     */
    public function handleEvents()
    {
        $time = time();
        if ($this->lastTick == 0 || ($this->lastTick + $this->wait <= $time)) {
            $this->doTick();
            $this->lastTick = $time;
        }
        $this->handleEventsAsync();
    }
}
