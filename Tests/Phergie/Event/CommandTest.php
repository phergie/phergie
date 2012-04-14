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
 * @copyright 2008-2012 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Tests
 */

/**
 * Unit test suite for Phergie_Event_Command.
 *
 * @category Phergie
 * @package  Phergie_Tests
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Tests
 */
class Phergie_Event_CommandTest extends PHPUnit_Framework_TestCase
{
    /**
     * Instance of the class to test
     *
     * @var Phergie_Event_Command
     */
    private $event;

    /**
     * Instantiates the class to test.
     *
     * @return void
     */
    public function setUp()
    {
        $this->event = new Phergie_Event_Command;
    }

    /**
     * Tests that an event is associated with no plugin by default.
     *
     * @return void
     */
    public function testGetPlugin()
    {
        $this->assertNull($this->event->getPlugin());
    }

    /**
     * Tests that a plugin can be associated with an event.
     *
     * @return void
     */
    public function testSetPlugin()
    {
        $plugin = $this->getMock('Phergie_Plugin_Abstract');
        $this->event->setPlugin($plugin);
        $this->assertSame($plugin, $this->event->getPlugin());
    }
}
