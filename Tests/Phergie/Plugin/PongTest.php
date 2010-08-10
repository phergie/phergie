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
 * @package   Phergie_Tests
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2010 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Tests
 */

/**
 * Unit test suite for Pherge_Plugin_Pong.
 *
 * @category Phergie
 * @package  Phergie_Tests
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Tests
 */
class Phergie_Plugin_PongTest extends Phergie_Plugin_TestCase
{
    /**
     * Test that a pong event is sent when a ping event is received.
     *
     * @return void
     */
    public function testPong()
    {
        $expected = 'irc.freenode.net';
        $event = $this->getMockEvent('ping', array($expected));
        $this->plugin->setEvent($event);
        $this->assertEmitsEvent('pong', array($expected));
        $this->plugin->onPing();
    }
}
