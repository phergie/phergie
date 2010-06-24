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
 * Unit test suite for the Phergie_Log class.
 *
 * @category Phergie
 * @package  Phergie_Tests
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Tests
 */
class Phergie_LogTest extends PHPUnit_Framework_TestCase
{
    /**
     * Logger instance being tested
     *
     * @var Phergie_Log
     */
    protected $log;

    /**
     * Initializes the logger instance.
     *
     * @return void
     */
    public function setUp()
    {
        $this->log = new Phergie_Log;
    }

    /**
     * Tests adding an adapter.
     *
     * @return void
     */
    public function testAddAdapter()
    {
        $adapter = new Phergie_Log_Mock;
        $return = $this->log->addAdapter($adapter);
        $this->assertSame($return, $this->log);
    }

    /**
     * Tests getting added adapters.
     *
     * @return void
     */
    public function testGetAdapters()
    {
        $adapters = $this->log->getAdapters();
        $this->assertEquals($adapters, array());

        $adapter = new Phergie_Log_Mock;
        $this->log->addAdapter($adapter);

        $adapters = $this->log->getAdapters();
        $this->assertContains($adapter, $adapters);
    }

    /**
     * Tests getting the log message format.
     *
     * @return void
     */
    public function testGetFormat()
    {
        $expected = '%time% DEBUG(%class%): %message%';
        $actual = $this->log->getFormat();
        $this->assertEquals($expected, $actual);
    }

    /**
     * Tests setting the log message format.
     *
     * @return void
     */
    public function testSetFormat()
    {
        $expected = 'test';
        $this->log->setFormat($expected);

        $actual = $this->log->getFormat();
        $this->assertEquals($expected, $actual);
    }

    /**
     * Tests logging messages.
     *
     * @depends testAddAdapter
     *
     * @return void
     */
    public function testLog()
    {
        $message = 'test';

        $adapter = new Phergie_Log_Mock;
        $this->log->addAdapter($adapter);

        $time = time();
        $this->log->log($message);
        $entry = date('H:i:s', $time) . ' DEBUG(' . __CLASS__ . '): ' . $message;

        $entries = $adapter->getEntries();
        $this->assertEquals($entries, array($entry));
    }
}
