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
 * @copyright 2008-2012 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie
 */

/**
 * Connection data processor which reads all connections looking
 * for a response.
 *
 * @category Phergie
 * @package  Phergie
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie
 */
class Phergie_Process_Standard extends Phergie_Process_Abstract
{
    /**
     * Obtains and processes incoming events, then sends resulting outgoing
     * events.
     *
     * @return void
     */
    public function handleEvents()
    {
        foreach ($this->connections as $connection) {
            $this->driver->setConnection($connection);
            $this->plugins->setConnection($connection);
            $this->plugins->onTick();

            if ($event = $this->driver->getEvent()) {
                $this->ui->onEvent($event, $connection);
                $this->plugins->setEvent($event);
                $this->plugins->preEvent();
                $this->plugins->{'on' . ucfirst($event->getType())}();
            }

            $this->processEvents($connection);
        }
    }
}
