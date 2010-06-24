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
 * Mock logging backend adapters for unit testing.
 *
 * @category Phergie
 * @package  Phergie
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie
 */
class Phergie_Log_Mock implements Phergie_Log_Interface
{
    /**
     * Written log entries
     *
     * @var string
     */
    protected $entries;

    /**
     * Constructor to initialize instance properties.
     *
     * @return void
     */
    public function __construct()
    {
        $this->entries = array();
    }

    /**
     * Implements Phergie_Log_Interface::write().
     *
     * @param string $message Log message to write
     *
     * @return void
     */
    public function write($message)
    {
        $this->entries[] = (string) $message;
    }

    /**
     * Returns all log entries written to the adapter.
     *
     * @return string Enumerated array of log entry strings
     */
    public function getEntries()
    {
        return $this->entries;
    }
}
