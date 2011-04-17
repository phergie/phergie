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
 * @copyright 2008-2011 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie
 */

/**
 * Exception related to outgoing events.
 *
 * @category Phergie
 * @package  Phergie
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie
 */
class Phergie_Event_Exception extends Phergie_Exception
{
    /**
     * Error indicating that an attempt was made to create an event of an
     * unknown type
     */
    const ERR_UNKNOWN_EVENT_TYPE = 1;

    /**
     * Error indicating that an event argument was specified, but either no
     * event type is set or the argument does not correspond to it
     */
    const ERR_INVALID_ARGUMENT = 2;

    /**
     * Error indicating that an operation requiring an event hostmask was
     * executed, but no hostmask was set
     */
    const ERR_MISSING_HOSTMASK = 3;

    /**
     * Error indicating that an undefined method was called on an event
     * instance
     */
    const ERR_INVALID_METHOD_CALL = 4;
}
