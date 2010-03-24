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
 * Unit test suite for Pherge_Plugin_Ping.
 *
 * @category Phergie
 * @package  Phergie_Tests
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie
 */
class Phergie_Plugin_PingTest extends Phergie_Plugin_TestCase
{
    protected $config = array('ping.ping'  => 10,
                              'ping.event' => 300);
    
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->setPlugin(new Phergie_Plugin_Ping);
    }

    /**
     * Test the lastEvent setter and getter
     */
    public function testSetGetLastEvent()
    {
        $expected = rand(100000,200000);
        $this->plugin->setLastEvent($expected);
        $this->assertEquals($expected,
                            $this->plugin->getLastEvent(),
                            'Assert that the last event was set and gotten ' .
                            'correctly');
    }

    /**
     * Test the lastPing setter and getter
     */
    public function testSetGetLastPing()
    {

        $expected = rand(100000,200000);
        $this->plugin->setLastPing($expected);
        $this->assertEquals($expected,
                            $this->plugin->getLastPing(),
                            'Assert that the last ping was set and gotten ' .
                            'correctly');
    }

    /**
     * Tests the onConnect hook
     */
    public function testOnConnect()
    {
        $time = time() - 1;
        // We need to make sure time() is going to be creater next time it is called
        
        $this->plugin->onConnect();
        $this->assertNull($this->plugin->getLastPing(), 
                          'onConnect should set last ping to null');
        $this->assertGreaterThan($time,
                                 $this->plugin->getLastEvent(),
                                 'onConnect should update lastEvent with the ' .
                                 'current timestamp');
        $this->assertLessThan($time + 2,
                              $this->plugin->getLastEvent(),
                              'onConnect should update lastEvent with the ' .
                              'current timestamp');
    }

    /**
     * Test that the preEvent method updates the lastEvent with the current time
     */
    public function testPreEvent()
    {
        $time = time() -1;
        $this->plugin->preEvent();
        $this->assertGreaterThan($time,
                                 $this->plugin->getLastEvent(),
                                 'Last event time was set properly on preEvent');
        $this->assertLessThan($time +2,
                              $this->plugin->getLastEvent(),
                              'Last Event time was set properly on preEvent');
    }

    /**
     * @todo Implement testOnPingResponse().
     */
    public function testOnPingResponse()
    {
        $this->plugin->setLastPing(time());
        $this->plugin->onPingResponse();
        $this->assertNull($this->plugin->getLastPing(),
                          'Last ping time should be null after onPingResponse');

    }

    /**
     * Test that the plugin issues a quit when the ping threashold
     * has been exceeded
     */
    public function testOnTickExceededPingThresholdQuits()
    {
        $this->plugin->setLastPing(1);
        $this->plugin->onTick();
        $this->assertHasEvent(Phergie_Event_Command::TYPE_QUIT);
    }
    
    /**
     * Test that the plugin issues a quit when the ping threashold
     * has been exceeded
     */
    public function testOnTickPingWithinThresholdDoesNotQuits()
    {
        $this->plugin->setLastPing(time());
        $this->plugin->onTick();
        $this->assertDoesNotHaveEvent(Phergie_Event_Command::TYPE_QUIT);
    }

    /**
     * Test that a ping is emitted when the event threashold is exceeded
     */
    public function testPingEmittedAfterThresholdExceeded()
    {
        $this->plugin->setLastEvent(time() - $this->config['ping.event'] - 1);
        $this->plugin->onTick();
        $this->assertHasEvent(Phergie_Event_Command::TYPE_PING);
        $events = $this->getResponseEvents(Phergie_Event_Command::TYPE_PING);
        foreach ($events as $event) {
            $this->assertEventEmitter($event,
                                      $this->plugin,
                    'Assert that the event was emitted by the tested plugin');
        }
    }

    /**
     * Test that no ping is emitted when the event thresthold is not exceeded
     */
    public function testNoPingEmittedWhenThresholdNotExceeded()
    {
        $this->plugin->setLastEvent(time() - $this->config['ping.event'] +1);
        $this->plugin->onTick();
        $this->assertDoesNotHaveEvent(Phergie_Event_Command::TYPE_PING);
    }

    public function tearDown()
    {
        $this->handler->clearEvents();
    }

}