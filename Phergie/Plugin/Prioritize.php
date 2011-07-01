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
 * @package   Phergie_Plugin_Prioritize
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2011 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Plugin_Prioritize
 */

/**
 * Prioritizes events such that they are executed in order from least to most 
 * destructive.
 *
 * @category Phergie 
 * @package  Phergie_Plugin_Prioritize
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Plugin_Prioritize
 */
class Phergie_Plugin_Prioritize extends Phergie_Plugin_Abstract
{
    /** 
     * Event types ordered by priority of execution
     *
     * @var array
     */
    protected $priority = array(
        'raw',
        'pass',
        'user',
        'ping',
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
        'action',
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
     * @return void 
     */
    public function preDispatch()
    {
        $events = $this->getEventHandler();

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
        $types = array_intersect($this->priority, array_keys($categorized));
        $prioritized = array();
        foreach ($types as $type) {
            $prioritized = array_merge($prioritized, $categorized[$type]);
        }

        // Replace the original events array with the prioritized one
        $events->replaceEvents($prioritized);
    }
}
