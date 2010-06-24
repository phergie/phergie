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
 * Unit test suite for the Phergie_Log_Stdout class.
 *
 * @category Phergie
 * @package  Phergie_Tests
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Tests
 */
class Phergie_Log_StdoutTest extends PHPUnit_Framework_TestCase
{
    /**
     * Adapter being tested
     *
     * @var Phergie_Log_Stdout
     */
    protected $adapter;

    /**
     * Initializes the adapter.
     *
     * @return void
     */
    public function setUp()
    {
        $this->adapter = new Phergie_Log_Stdout;
    }

    /**
     * Tests writing to the adapter.
     *
     * @return void
     */
    public function testWrite()
    {
        ob_start();

        $expected = 'test';
        $this->adapter->write($expected);
        $expected .= PHP_EOL;

        $actual = ob_get_clean();
        $this->assertEquals($expected, $actual);
    }
}
