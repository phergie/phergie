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
 * @package   Phergie_Plugin_Time
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2010 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Plugin_Time
 */

/**
 * Helper plugin to assist other plugins with time manipulation, display.
 *
 * Any shared time-related code should go into this class.
 *
 * @category Phergie
 * @package  Phergie_Plugin_Time
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Plugin_Time
 */
class Phergie_Plugin_Time extends Phergie_Plugin_Abstract
{
    /**
     * Returns the time interval between the current time and a given 
     * timestamp. 
     *
     * @param string $timestamp Timestamp compatible with strtotime()
     *
     * @return string
     */
    public function getCountdown($timestamp)
    {
        $time = time() - strtotime($timestamp); 
        $return = array();

        $days = floor($time / 86400);
        if ($days > 0) {
            $return[] = $days . 'd';
            $time %= 86400;
        }

        $hours = floor($time / 3600);
        if ($hours > 0) {
            $return[] = $hours . 'h';
            $time %= 3600;
        }

        $minutes = floor($time / 60);
        if ($minutes > 0) {
            $return[] = $minutes . 'm';
            $time %= 60;
        }

        if ($time > 0 || count($return) <= 0) {
            $return[] = ($time > 0 ? $time : '0') . 's';
        }

        return implode(' ', $return);
    }
}
