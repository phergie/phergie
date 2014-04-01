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
 * Unit test suite for Phergie_Event_Response.
 *
 * @category Phergie
 * @package  Phergie_Tests
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Tests
 */
class Phergie_Event_ResponseTest extends PHPUnit_Framework_TestCase
{
    /**
     * Instance of the class to test
     *
     * @var Phergie_Event_Response
     */
    private $event;

    /**
     * Instantiates the class to test.
     *
     * @return void
     */
    public function setUp()
    {
        $this->event = new Phergie_Event_Response;
    }

    /**
     * Tests that the event has no response code set by default.
     *
     * @return void
     */
    public function testGetCode()
    {
        $this->assertNull($this->event->getCode());
    }

    /**
     * Tests that the event response code can be changed.
     *
     * @return void
     */
    public function testSetCode()
    {
        $code = Phergie_Event_Response::ERR_NOSUCHNICK;
        $this->event->setCode($code);
        $this->assertSame($code, $this->event->getCode());
    }

    /**
     * Tests that the event has no response description set by default.
     *
     * @return void
     */
    public function testGetDescription()
    {
        $this->assertNull($this->event->getDescription());
    }

    /**
     * Tests that the event response description can be changed.
     *
     * @return void
     */
    public function testSetDescription()
    {
        $description = 'No such nick';
        $this->event->setDescription($description);
        $this->assertSame($description, $this->event->getDescription());
    }

    /**
     * Tests that the event has no raw data set by default.
     *
     * @return void
     */
    public function testGetRawData()
    {
        $this->assertNull($this->event->getRawData());
    }

    /**
     * Tests that the event raw data can be changed.
     *
     * @return void
     */
    public function testSetRawData()
    {
        $description = ':irc.freeode.net 401 No such nick';
        $this->event->setRawData($description);
        $this->assertSame($description, $this->event->getRawData());
    }
}
