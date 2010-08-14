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
 * Unit test suite for Pherge_Event_Abstract.
 *
 * @category Phergie
 * @package  Phergie_Tests
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Tests
 */
class Phergie_Event_AbstractTest extends PHPUnit_Framework_TestCase
{
    /**
     * Instance of the class to test
     *
     * @var Phergie_Event_Abstract
     */
    private $event;

    /**
     * Instantiates the class to test.
     *
     * @return void
     */
    public function setUp()
    {
        $this->event = $this->getMockForAbstractClass('Phergie_Event_Abstract');
    }

    /**
     * Tests that the event has no default type.
     *
     * @return void
     */
    public function testGetType()
    {
        $this->assertNull($this->event->getType());
    }

    /**
     * Tests that the event type can be changed.
     *
     * @return void
     */
    public function testSetType()
    {
        $type = 'foo';
        $this->event->setType($type);
        $this->assertEquals($type, $this->event->getType());
    }
}
