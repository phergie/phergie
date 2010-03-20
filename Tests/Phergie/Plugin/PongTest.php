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
 * Unit test suite for Pherge_Plugin_Pong.
 *
 * @category Phergie
 * @package  Phergie_Tests
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie
 */
class Phergie_Plugin_PongTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Phergie_Plugin_Pong
     */
    protected $object;
    /**
     * @var Phergie_Event_Handler
     */
    protected $handler;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $connection = new Phergie_Connection();
        $this->handler = new Phergie_Event_Handler();
        $this->object = new Phergie_Plugin_Pong;
        $this->object->setEventHandler($this->handler);
        $this->object->setConnection($connection);
        $event = new Phergie_Event_Request();
        $event->setType(Phergie_Event_Request::TYPE_PING);
        $this->object->setEvent($event);
    }

    /**
     * Test that when a ping is received, a Phergie_Event_Command::TYPE_PONG
     * is set to the handler
     */
    public function testOnPing()
    {
        $this->object->onPing();
        $this->assertTrue($this->handler->hasEventOfType(Phergie_Event_Command::TYPE_PONG));
    }
}