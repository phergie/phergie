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
 * Handles events initiated by plugins.
 *
 * @category Phergie
 * @package  Phergie
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie
 */
class Phergie_Event_Handler implements IteratorAggregate, Countable
{
    /**
     * Current queue of events
     *
     * @var array
     */
    protected $events;

    /**
     * Constructor to initialize the event queue.
     *
     * @return void
     */
    public function __construct()
    {
        $this->events = array();
    }

    /**
     * Adds an event to the queue.
     *
     * @param Phergie_Plugin_Abstract $plugin Plugin originating the event
     * @param string                  $type   Event type, corresponding to a
     *        Phergie_Event_Command::TYPE_* constant
     * @param array                   $args   Optional event arguments
     *
     * @return Phergie_Event_Handler Provides a fluent interface
     */
    public function addEvent(Phergie_Plugin_Abstract $plugin, $type,
        array $args = array()
    ) {
        if (!defined('Phergie_Event_Request::TYPE_' . strtoupper($type))) {
            throw new Phergie_Event_Exception(
                'Unknown event type "' . $type . '"',
                Phergie_Event_Exception::ERR_UNKNOWN_EVENT_TYPE
            );
        }

        $event = new Phergie_Event_Command;
        $event
            ->setPlugin($plugin)
            ->setType($type)
            ->setArguments($args);

        $this->events[] = $event;

        return $this;
    }

    /**
     * Returns the current event queue.
     *
     * @return array Enumerated array of Phergie_Event_Command objects
     */
    public function getEvents()
    {
        return $this->events;
    }

    /**
     * Clears the event queue.
     *
     * @return Phergie_Event_Handler Provides a fluent interface
     */
    public function clearEvents()
    {
        $this->events = array();
        return $this;
    }

    /**
     * Replaces the current event queue with a given queue of events.
     *
     * @param array $events Ordered list of objects of the class
     *        Phergie_Event_Command
     *
     * @return Phergie_Event_Handler Provides a fluent interface
     */
    public function replaceEvents(array $events)
    {
        $this->events = $events;
        return $this;
    }

    /**
     * Returns whether an event of the given type exists in the queue.
     *
     * @param string $type Event type from Phergie_Event_Request::TYPE_*
     *        constants
     *
     * @return bool TRUE if an event of the specified type exists in the
     *         queue, FALSE otherwise
     */
    public function hasEventOfType($type)
    {
        foreach ($this->events as $event) {
            if ($event->getType() == $type) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns a list of events of a specified type.
     *
     * @param string $type Event type from Phergie_Event_Request::TYPE_*
     *        constants
     *
     * @return array Array containing event instances of the specified type
     *         or an empty array if no such events were found
     */
    public function getEventsOfType($type)
    {
        $events = array();
        foreach ($this->events as $event) {
            if ($event->getType() == $type) {
                $events[] = $event;
            }
        }
        return $events;
    }

    /**
     * Removes a single event from the event queue.
     *
     * @param Phergie_Event_Command $event Event to remove
     *
     * @return Phergie_Event_Handler Provides a fluent interface
     */
    public function removeEvent(Phergie_Event_Command $event)
    {
        $key = array_search($event, $this->events);
        if ($key !== false) {
            unset($this->events[$key]);
        }
        return $this;
    }

    /**
     * Returns an iterator for the current event queue.
     *
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->events);
    }

    /**
     * Returns the number of events in the event queue
     *
     * @return int number of queued events
     */
    public function count()
    {
        return count($this->events);
    }
}
