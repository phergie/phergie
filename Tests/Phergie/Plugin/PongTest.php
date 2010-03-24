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

require_once(dirname(__FILE__) . '/TestCase.php');

/**
 * Unit test suite for Pherge_Plugin_Pong.
 *
 * @category Phergie
 * @package  Phergie_Tests
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie
 */
class Phergie_Plugin_PongTest extends Phergie_Plugin_TestCase
{
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->setPlugin(new Phergie_Plugin_Pong);
    }

    /**
     * Test that when a ping is received, a Phergie_Event_Command::TYPE_PONG
     * is set to the handler
     *
     * @event Phergie_Event_Command::TYPE_PING
     */
    public function testOnPing()
    {
        $this->plugin->onPing();
        $this->assertHasEvent(Phergie_Event_Command::TYPE_PONG);
    }

    /**
     * Test that when a ping is received, a Phergie_Event_Command::TYPE_PONG
     * is set to the handler
     *
     * @event Phergie_Event_Command::TYPE_PING
     */
    public function testOnPingResponseArguement()
    {
        $this->plugin->onPing();
        $this->assertHasEvent(Phergie_Event_Command::TYPE_PONG);
        $events = $this->getResponseEvents(Phergie_Event_Command::TYPE_PONG);
        $this->assertTrue(count($events) === 1, 'Assert that only one pong is emitted');
        $this->assertEventEmitter(current($events),
                                  $this->plugin,
                                  'Assert that the tested plugin emitted the event');

    }

}
