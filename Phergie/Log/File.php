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
 * File logging backend adapter.
 *
 * @category Phergie
 * @package  Phergie
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie
 */
class Phergie_Log_File implements Phergie_Log_Interface
{
    /**
     * Path to a file to receive log entries
     *
     * @var array
     */
    protected $file;

    /**
     * Constructor to initialize instance properties.
     *
     * @param string $file Path to the file to receive log entries
     *
     * @return void
     */
    public function __construct($file)
    {
        if (!is_writable($file)) {
            throw new Phergie_Log_Exception(
                'File "' . $file . '" is not writable',
                Phergie_Log_Exception::ERR_SOURCE_NOT_WRITABLE
            );
        }

        $this->file = $file;
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
        file_put_contents($this->file, $message . PHP_EOL, FILE_APPEND);
    }
}
