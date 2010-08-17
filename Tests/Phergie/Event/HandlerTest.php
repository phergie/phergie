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
 * Unit test suite for Phergie_Event_Handler.
 *
 * @category Phergie
 * @package  Phergie_Tests
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Tests
 */
class Phergie_Event_HandlerTest extends PHPUnit_Framework_TestCase
{
    /**
     * Instance of the class to test
     *
     * @var Phergie_Event_Handler
     */
    private $events;

    /**
     * Plugin associated with an event added to the handler
     *
     * @var Phergie_Plugin_Abstract
     */
    private $plugin;

    /**
     * Type of event added to the handler
     *
     * @var string
     */
    private $type = 'privmsg';

    /**
     * Arguments for an event added to the handler
     *
     * @var array
     */
    private $args = array('#channel', 'text');

    /**
     * Instantiates the class to test.
     *
     * @return void
     */
    public function setUp()
    {
        $this->events = new Phergie_Event_Handler;
        $this->plugin = $this->getMockForAbstractClass('Phergie_Plugin_Abstract');
    }

    /**
     * Tests that the handler contains no events by default.
     *
     * @return void
     */
    public function testGetEvents()
    {
        $expected = array();
        $actual = $this->events->getEvents();
        $this->assertEquals($expected, $actual);
    }

    /**
     * Adds a mock event to the handler.
     *
     * @return void
     */
    private function addMockEvent()
    {
        $this->events->addEvent($this->plugin, $this->type, $this->args);
    }

    /**
     * Tests that the handler can receive a new event.
     *
     * @return void
     */
    public function testAddEventWithValidData()
    {
        $this->addMockEvent();
        $events = $this->events->getEvents();
        $event = array_shift($events);
        $this->assertType('Phergie_Event_Command', $event);
        $this->assertSame($this->plugin, $event->getPlugin());
        $this->assertSame($this->type, $event->getType());
        $this->assertSame($this->args, $event->getArguments());
    }

    /**
     * Tests that attempting to add an event to the handler with an invalid
     * type results in an exception.
     *
     * @return void
     */
    public function testAddEventWithInvalidType()
    {
        $type = 'foo';
        try {
            $this->events->addEvent($this->plugin, $type);
            $this->fail('Expected exception was not thrown');
        } catch (Phergie_Event_Exception $e) {
            if ($e->getCode() != Phergie_Event_Exception::ERR_UNKNOWN_EVENT_TYPE) {
                $this->fail('Unexpected exception code ' . $e->getCode());
            }
        }
    }

    /**
     * Tests that the events contained within the handler can be
     * collectively removed.
     *
     * @return void
     * @depends testGetEvents
     * @depends testAddEventWithValidData
     */
    public function testClearEvents()
    {
        $this->addMockEvent();
        $this->events->clearEvents();
        $expected = array();
        $actual = $this->events->getEvents();
        $this->assertSame($expected, $actual);
    }

    /**
     * Tests that the events contained within the handler can be replaced
     * with a different set of events.
     *
     * @return void
     * @depends testAddEventWithValidData
     */
    public function testReplaceEvents()
    {
        $this->addMockEvent();
        $expected = array();
        $this->events->replaceEvents($expected);
        $actual = $this->events->getEvents();
        $this->assertSame($expected, $actual);
    }

    /**
     * Tests that the handler can accurately identify whether it has an
     * event of a specified type.
     *
     * @return void
     * @depends testAddEventWithValidData
     */
    public function testHasEventOfType()
    {
        $this->assertFalse($this->events->hasEventOfType($this->type));
        $this->addMockEvent();
        $this->assertTrue($this->events->hasEventOfType($this->type));
    }

    /**
     * Tests that the handler can return events it contains that are of a
     * specified type.
     *
     * @return void
     * @depends testAddEventWithValidData
     */
    public function testGetEventsOfType()
    {
        $expected = array();
        $actual = $this->events->getEventsOfType($this->type);
        $this->assertSame($expected, $actual);

        $this->addMockEvent();
        $expected = $this->events->getEvents();
        $actual = $this->events->getEventsOfType($this->type);
        $this->assertSame($expected, $actual);
    }

    /**
     * Tests that an event can be removed from the handler.
     *
     * @return void
     * @depends testAddEventWithValidData
     */
    public function testRemoveEvent()
    {
        $this->addMockEvent();
        $events = $this->events->getEvents();
        $event = array_shift($events);
        $this->events->removeEvent($event);
        $expected = array();
        $actual = $this->events->getEvents();
        $this->assertSame($expected, $actual);
    }

    /**
     * Tests that the handler supports iteration of the events it contains.
     *
     * @return void
     * @depends testAddEventWithValidData
     */
    public function testImplementsGetIterator()
    {
        $reflector = new ReflectionClass('Phergie_Event_Handler');
        $this->assertTrue($reflector->implementsInterface('IteratorAggregate'));
        $this->addMockEvent();
        $events = $this->events->getEvents();
        $expected = array_shift($events);
        foreach ($this->events as $actual) {
            $this->assertSame($expected, $actual);
        }
    }

    /**
     * Tests that the handler supports returning a count of the events it
     * contains.
     *
     * @return void
     * @depends testAddEventWithValidData
     */
    public function testImplementsCountable()
    {
        $reflector = new ReflectionClass('Phergie_Event_Handler');
        $this->assertTrue($reflector->implementsInterface('Countable'));

        $expected = 0;
        $actual = count($this->events);
        $this->assertSame($expected, $actual);

        $this->addMockEvent();
        $expected = 1;
        $actual = count($this->events);
        $this->assertSame($expected, $actual);
    }
}
