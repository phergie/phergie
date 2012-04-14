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
 * Exception related to a connection to an IRC server.
 *
 * @category Phergie
 * @package  Phergie
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie
 */
class Phergie_Connection_Exception extends Phergie_Exception
{
    /**
     * Error indicating that an operation was attempted requiring a value
     * for a specific configuration setting, but none was set
     */
    const ERR_REQUIRED_SETTING_MISSING = 1;

    /**
     * Error indicating that a connection is configured to use a transport,
     * but that transport is not supported by the current PHP installation
     */
    const ERR_TRANSPORT_NOT_SUPPORTED = 2;

    /**
     * Error indicating that a connection is configured to use an encoding,
     * but that encoding is not supported by the current PHP installation
     */
    const ERR_ENCODING_NOT_SUPPORTED = 3;
}
