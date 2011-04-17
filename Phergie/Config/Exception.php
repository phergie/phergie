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
 * Exception related to configuration.
 *
 * @category Phergie
 * @package  Phergie
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie
 */
class Phergie_Config_Exception extends Phergie_Exception
{
    /**
     * Error indicating that an attempt was made to read a configuration
     * file that does not exist
     */
    const ERR_FILE_NOT_FOUND = 1;

    /**
     * Error indicating that an attempt was made to read a configuration
     * file when the current bot process does not have sufficient
     * permissions to read it
     */
    const ERR_FILE_NOT_READABLE = 2;

    /**
     * Error indicating that a read configuration file does not return an
     * array
     */
    const ERR_ARRAY_NOT_RETURNED = 3;
}
