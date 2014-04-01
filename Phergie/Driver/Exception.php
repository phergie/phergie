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
 * Exception related to driver operations.
 *
 * @category Phergie
 * @package  Phergie
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie
 */
class Phergie_Driver_Exception extends Phergie_Exception
{
    /**
     * Error indicating that an operation was requested requiring an active
     * connection before one had been set
     */
    const ERR_NO_ACTIVE_CONNECTION = 1;

    /**
     * Error indicating that an attempt to initiate a connection failed
     */
    const ERR_CONNECTION_ATTEMPT_FAILED = 2;

    /**
     * Error indicating that an attempt to send data via a connection failed
     */
    const ERR_CONNECTION_WRITE_FAILED = 3;
}
