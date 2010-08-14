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
 * Unit test suite for Phergie_Plugin_Ping.
 *
 * @category Phergie
 * @package  Phergie_Tests
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Tests
 */
class Phergie_Plugin_PingTest extends Phergie_Plugin_TestCase
{
    /**
     * Tests that the last ping and event are initialized on connection to
     * the server.
     *
     * @return void
     */
    public function testOnConnect()
    {
        $this->plugin->onConnect();

        $expected = time();
        $actual = $this->plugin->getLastEvent();
        $this->assertEquals($expected, $actual);

        $expected = null;
        $actual = $this->plugin->getLastPing();
        $this->assertEquals($expected, $actual);
    }

    /**
     * Tests that the last event is reset when an event occurs.
     *
     * @return void
     */
    public function testPreEvent()
    {
        $this->plugin->preEvent();

        $expected = time();
        $actual = $this->plugin->getLastEvent();
        $this->assertEquals($expected, $actual);
    }

    /**
     * Tests that the last ping is reset when a ping is received.
     *
     * @return void
     */
    public function testOnPingResponse()
    {
        $this->plugin->onPingResponse();

        $expected = null;
        $actual = $this->plugin->getLastPing();
        $this->assertEquals($expected, $actual);
    }

    /**
     * Tests that the test suite is able to manipulate the value of the last
     * event.
     *
     * @return void
     */
    public function testSetLastEvent()
    {
        $expected = time() + 1;
        $this->plugin->setLastEvent($expected);
        $actual = $this->plugin->getLastEvent();
        $this->assertEquals($expected, $actual);

        $this->plugin->setLastEvent();
        $expected = time();
        $actual = $this->plugin->getLastEvent();
        $this->assertEquals($expected, $actual);

        try {
            $this->plugin->setLastEvent('foo');
            $this->fail('Expected exception was not thrown');
        } catch (Exception $e) { }
    }

    /**
     * Tests that the test suite is able to manipulate the value of the last
     * ping.
     *
     * @return void
     */
    public function testSetLastPing()
    {
        $expected = time() + 1;
        $this->plugin->setLastPing($expected);
        $actual = $this->plugin->getLastPing();
        $this->assertEquals($expected, $actual);

        $this->plugin->setLastPing();
        $expected = time();
        $actual = $this->plugin->getLastPing();
        $this->assertEquals($expected, $actual);

        try {
            $this->plugin->setLastPing('foo');
            $this->fail('Expected exception was not thrown');
        } catch (Exception $e) { }
    }

    /**
     * Tests that a ping event is sent after the appropriate time period has
     * lapsed since receiving an event.
     *
     * @depends testSetLastEvent
     * @return void
     */
    public function testPing()
    {
        $pingEvent = 10;
        $this->setConfig('ping.event', $pingEvent);
        $lastEvent = time() - ($pingEvent + 1);
        $this->plugin->setLastEvent($lastEvent);
        $expected = time();
        $this->assertEmitsEvent('ping', array($this->nick, $expected));
        $this->plugin->onTick();
        $actual = $this->plugin->getLastPing();
        $this->assertEquals($expected, $actual);
    }

    /**
     * Tests that a quit event is sent after the appropriate time period has
     * lapsed since sending a ping event.
     *
     * @depends testPing
     * @return void
     */
    public function testQuit()
    {
        $pingPing = 10;
        $this->setConfig('ping.ping', $pingPing);
        $lastPing = time() - ($pingPing + 1);
        $this->plugin->setLastPing($lastPing);
        $this->assertEmitsEvent('quit');
        $this->plugin->onTick();
    }
}
