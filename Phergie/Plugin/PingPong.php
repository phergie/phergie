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
 * @package   Phergie_Plugin_PingPong
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2011 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Plugin_PingPong
 */

/**
 * Implements "test" and "ping" commands to test the bot's responsiveness.
 *
 * @category Phergie
 * @package  Phergie_Plugin_PingPong
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Plugin_PingPong
 */
class Phergie_Plugin_PingPong extends Phergie_Plugin_Abstract
{
    /**
     * Responds to "ping" messages.
     *
     * @return void
     */
    public function onCommandPing()
    {
        $this->doPrivmsg(
            $this->event->getSource(),
            $this->event->getNick() . ': pong'
        );
    }
    /**
     * Responds to "test" messages.
     *
     * @return void
     */
    public function onCommandTest()
    {
        $this->doPrivmsg(
            $this->event->getSource(),
            $this->event->getNick() . ': passed'
        );
    }
}
