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
 * asynchronous manner. Will also cause the appication tick at
 * the user defined wait time.
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
     * How long to poll for stream activity
     *
     * @var int
     */
    protected $wait;

    /**
     * Records when the appication last performed a tick
     *
     * @var int
     */
    protected $lastTick = 0;

    /**
     * Overrides the parent class to setup wait option.
     *
     * @param Phergie_Bot $bot     Main bot class
     * @param array       $options processor arguments
     *
     * @return void
     */
    public function __construct(Phergie_Bot $bot, $options)
    {
        if (!isset($options['wait']) && !is_int($options['wait'])) {
            throw new Phergie_Process_Exception('Option "wait" for Async must be an int.');
        }
            
        $this->wait = $options['wait'];

        parent::__construct($bot, $options);
    }

    /**
     * Wait for stream activity and perform event processing 
     * on connections with data to read.
     *
     * @return void
     */
    protected function handleEventsAsync()
    {
        if ($keys = $this->driver->activeReadSockets($this->wait)) {
            $connections = $this->connections->getConnections($keys);
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
    }

    /**
     * Perform appication tick event on all plugins and connections.
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
