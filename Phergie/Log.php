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
 * Logging component designed mainly for use in debugging plugins.
 *
 * @category Phergie
 * @package  Phergie
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie
 */
class Phergie_Log
{
    /**
     * List of logging backend adapters
     *
     * @var array
     */
    protected $adapters;

    /**
     * Format of logged messages
     *
     * @var string
     */
    protected $format = '%time% DEBUG(%class%): %message%';

    /**
     * Constructor to initialize instance properties.
     *
     * @return void
     */
    public function __construct()
    {
        $this->adapters = array();
    }

    /**
     * Adds a logging adapter.
     *
     * @param Phergie_Log_Interface $adapter Adapter to add
     *
     * @return Phergie_Log Provides a fluent interface
     */
    public function addAdapter(Phergie_Log_Interface $adapter)
    {
        $this->adapters[] = $adapter;
        return $this;
    }

    /**
     * Returns a list of adding logging adapters.
     *
     * @return array Enumerated array of objects implementing the
     *         Phergie_Log_Interface interface
     */
    public function getAdapters()
    {
        return $this->adapters;
    }

    /**
     * Sets the log message format to use.
     *
     * @param string $format Format string where %time%, %class%, and
     *        %message% are supported placeholders for log data
     *
     * @return Phergie_Log Provides a fluent interface
     */
    public function setFormat($format)
    {
        $this->format = $format;
        return $this;
    }

    /**
     * Returns the log format in use.
     *
     * @return string Log format string
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * Writes a message to all logging adapters.
     *
     * @param string $message Message to log
     *
     * @return Phergie_Log Provides a fluent interface
     */
    public function log($message)
    {
        $time = date('H:i:s');
        $message = (string) $message;

        $backtrace = debug_backtrace();
        $object = $backtrace[1]['object'];
        if ($object instanceof Phergie_Plugin_Abstract) {
            $class = $object->getName();
        } else {
            $class = get_class($object);
        }

        $data = array(
            'time' => $time,
            'message' => $message,
            'class' => $class
        );

        $entry = $this->format;
        foreach ($data as $key => $value) {
            $entry = str_replace('%' . $key . '%', $value, $entry);
        }

        foreach ($this->adapters as $adapter) {
            $adapter->write($entry);
        }

        return $this;
    }
}
