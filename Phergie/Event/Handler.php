<?php

/**
 * Handles events initiated by plugins. 
 */
class Phergie_Event_Handler implements IteratorAggregate
{
    /**
     * Current queue of events
     *
     * @var array
     */
    protected $_events;

    /**
     * Constructor to initialize the event queue.
     *
     * @return void
     */
    public function __construct()
    {
        $this->_events = array();
    }

    /**
     * Adds an event to the queue.
     *
     * @param Phergie_Plugin_Abstract $plugin Plugin originating the event
     * @param string $type Event type, corresponding to a Phergie_Event_Command::TYPE_* constant
     * @param array $args Optional event arguments
     * @return Phergie_Event_Handler Provides a fluent interface
     */
    public function addEvent(Phergie_Plugin_Abstract $plugin, $type, array $args = array())
    {
        if (!defined('Phergie_Event_Command::TYPE_' . strtoupper($type))) {
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

        $this->_events[] = $event;

        return $this;
    }

    /**
     * Clears the event queue.
     *
     * @return Phergie_Event_Handler Provides a fluent interface
     */
    public function clearEvents()
    {
        $this->_events = array();
        return $this;
    }

    /**
     * Replaces the current event queue with a given queue of events.
     *
     * @param array $events Ordered list of objects of the class 
     *        Phergie_Event_Command
     * @return Phergie_Event_Handler Provides a fluent interface
     */
    public function replaceEvents(array $events)
    {
        $this->_events = $events;
        return $this;
    }

    /**
     * Returns whether an event of the given type exists in the queue.
     *
     * @param string $type Event type from Phergie_Event_Request::TYPE_* 
     *        constants
     * @return bool TRUE if an event of the specified type exists in the 
     *         queue, FALSE otherwise
     */
    public function hasEventOfType($type)
    {
        foreach ($this->_events as $event) {
            if ($event->getType() == $type) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns an iterator for the current event queue.
     *
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->_events);
    }
}
