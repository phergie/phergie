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
 * @copyright 2008-2010 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie
 */

/**
 * Exception related to plugin handling.
 *
 * @category Phergie
 * @package  Phergie
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie
 */
class Phergie_Plugin_Exception extends Phergie_Exception
{
    /**
     * Error indicating that a path containing plugins was specified, but
     * did not reference a readable directory
     */
    const ERR_DIRECTORY_NOT_READABLE = 1;

    /**
     * Error indicating that an attempt was made to locate the class for a
     * specified plugin, but the class could not be found
     */
    const ERR_CLASS_NOT_FOUND = 2;

    /**
     * Error indicating that an attempt was made to locate the class for a
     * specified plugin, but that the found class did not extend the base
     * plugin class
     */
    const ERR_INCORRECT_BASE_CLASS = 3;

    /**
     * Error indicating that an attempt was made to locate the class for a
     * specified plugin, but that the found class cannot be instantiated
     */
    const ERR_CLASS_NOT_INSTANTIABLE = 4;

    /**
     * Error indicating that an attempt was made to access a plugin that had
     * not been loaded and autoloading was not enabled to load it
     */
    const ERR_PLUGIN_NOT_LOADED = 5;

    /**
     * Error indicating that an attempt was made to access the configuration
     * handler before one had been set
     */
    const ERR_NO_CONFIG_HANDLER = 6;

    /**
     * Error indicating that an attempt was made to access the plugin
     * handler before one had been set
     */
    const ERR_NO_PLUGIN_HANDLER = 7;

    /**
     * Error indicating that an attempt was made to access the event
     * handler before one had been set
     */
    const ERR_NO_EVENT_HANDLER = 8;

    /**
     * Error indicating that an attempt was made to access the connection
     * before one had been set
     */
    const ERR_NO_CONNECTION = 9;

    /**
     * Error indicating that an attempt was made to access the current
     * incoming event before one had been set
     */
    const ERR_NO_EVENT = 10;

    /**
     * Error indicating that a dependency of the plugin was unavailable at
     * the time that an attempt was made to load it
     */
    const ERR_REQUIREMENT_UNSATISFIED = 11;

    /**
     * Error indicating that a call was made to a nonexistent plugin method
     * and that its __call() implementation did not process that call as an
     * attempt to trigger an event - this is intended to aid in debugging of
     * such situations
     */
    const ERR_INVALID_CALL = 12;

    /**
     * Error indicating that a fatal runtime issue was encountered within a
     * plugin
     */
    const ERR_FATAL_ERROR = 13;

    /**
     * Error indicating that an attempt was made to access the logger before
     * one had been set
     */
    const ERR_NO_LOGGER = 14;
}
