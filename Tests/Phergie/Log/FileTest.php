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
 * @package   Phergie_Tests
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2010 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Tests
 */

/**
 * Unit test suite for the Phergie_Log_File class.
 *
 * @category Phergie
 * @package  Phergie_Tests
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Tests
 */
class Phergie_Log_FileTest extends PHPUnit_Framework_TestCase
{
    /**
     * Adapter being tested
     *
     * @var Phergie_Log_File
     */
    protected $adapter;

    /**
     * Tests specifying a file that is not writable.
     *
     * @expectedException Phergie_Log_Exception
     *
     * @return void
     */
    public function testSpecifyingNonexistentFile()
    {
        $adapter = new Phergie_Log_File('/path/to/nonexistent/file');
    }

    /**
     * Tests writable a log message to the file.
     *
     * @return void
     */
    public function testWrite()
    {
        $file = tempnam(sys_get_temp_dir(), __CLASS__);
        $adapter = new Phergie_Log_File($file);

        $expected = 'test';
        $adapter->write($expected);
        $expected .= PHP_EOL;

        $actual = file_get_contents($file);
        $this->assertEquals($expected, $actual);

        unlink($file);
    }
}
