<?php

require_once 'Phergie/Plugin/Abstract.php';

/**
 * Prioritizes events such that they are executed in order from least to most 
 * destructive.
 */
class Phergie_Plugin_Prioritize extends Phergie_Plugin_Abstract
{
    /** 
     * Event types ordered by priority of execution
     *
     * @var array
     */
    protected $_priority = array(
        'raw',
        'pass',
        'user',
        'pong',
        'notice',
        'join',
        'list',
        'names',
        'version',
        'stats',
        'links',
        'time',
        'trace',
        'admin',
        'info',
        'who',
        'whois',
        'whowas',
        'mode',
        'privmsg',
        'nick',
        'topic',
        'invite',
        'kill',
        'part',
        'quit'
    );  

    /**
     * Prioritizes events from least to most destructive. 
     *
     * @param array $events Events to prioritize
     * @return void 
     */
    public function preDispatch(array &$events)
    {
        // Categorize events by type
        $categorized = array();
        foreach ($events as $event) {
            $type = $event->getType();
            if (!isset($categorized[$type])) {
                $categorized[$type] = array();
            }
            $categorized[$type][] = $event;
        }

        // Order events by type from least to most destructive
        $types = array_intersect($this->_priority, array_keys($categorized));
        $prioritized = array();
        foreach ($types as $type) {
            $prioritized = array_merge($prioritized, $categorized[$type]);
        }

        // Replace the original events array with the prioritized one
        $events = $prioritized;
    }
}
