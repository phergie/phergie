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

require_once 'PHPUnit/Framework.php';

// Handler requires Phergie_Autoload to function properly
// Testing of Phergie_Autoload should happen in a different test suite
require_once dirname(__FILE__) . '/../../../Phergie/Autoload.php';
Phergie_Autoload::registerAutoloader();

/**
 * Unit test suite for Pherge_Plugin_Handler
 *
 * @category Phergie
 * @package  Tests
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie
 */
class Phergie_Plugin_HandlerTest extends PHPUnit_Framework_TestCase
{
    protected $handler;

    /**
     * Sets up a new handler instance before each test.
     *
     * @return void
     */
    public function setUp()
    {
        $this->handler = new Phergie_Plugin_Handler();
    }

    /**
     * Destroys the handler instance after each test
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->handler);
    }

    /**
     * Ensures that we can iterate over the handler
     *
     * @return void
     */
    public function testImplementsIterator()
    {
        $reflection = new ReflectionObject($this->handler);
        $this->assertTrue(
            $reflection->implementsInterface('IteratorAggregate')
        );
    }

    /**
     * Ensures a newly instantiated handler does not have plugins associated
     * with it
     *
     * @depends testImplementsIterator
     * @return void
     */
    public function testEmptyHandlerHasNoPlugins()
    {
        $count = 0;
        foreach ($this->handler as $plugin) {
            $count++;
        }

        $this->assertEquals(0, $count);
    }
}
