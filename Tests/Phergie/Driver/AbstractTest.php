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
 * Unit test suite for Phergie_Driver_Abstract.
 *
 * @category Phergie
 * @package  Phergie_Tests
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Tests
 */
class Phergie_Driver_AbstractTest extends Phergie_TestCase
{
    /**
     * Instance of the class to test
     *
     * @var Phergie_Driver_Abstract
     */
    private $driver;

    /**
     * Instantiates the class to test.
     *
     * @return void
     */
    public function setUp()
    {
        $this->driver = $this->getMockForAbstractClass('Phergie_Driver_Abstract');
    }

    /**
     * Tests that attempting to retrieve a connection before one is set
     * results in an exception.
     *
     * @return void
     */
    public function testGetConnectionThrowsException()
    {
        try {
            $this->driver->getConnection();
            $this->fail('Expected exception not thrown');
        } catch (Phergie_Driver_Exception $e) {
            if ($e->getCode() != Phergie_Driver_Exception::ERR_NO_ACTIVE_CONNECTION) {
                $this->fail('Unexpected exception code ' . $e->getCode());
            }
        }
    }

    /**
     * Tests setting a connection and retrieving it afterward.
     *
     * @return void
     */
    public function testSetConnection()
    {
        $connection = $this->getMockConnection();
        $this->driver->setConnection($connection);
        $this->assertSame($connection, $this->driver->getConnection());
    }
}
