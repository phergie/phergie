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
 * Unit test suite for Phergie_Connection_Handler.
 *
 * @category Phergie
 * @package  Phergie_Tests
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Tests
 */
class Phergie_Connection_HandlerTest extends Phergie_TestCase
{
    /**
     * Instance of the class to test
     *
     * @var Phergie_Connection_Handler
     */
    protected $connections;

    /**
     * Instantiates the class to test.
     *
     * @return void
     */
    public function setUp()
    {
        $this->connections = new Phergie_Connection_Handler;
    }

    /**
     * Tests that the class implements the Countable interface.
     *
     * @return void
     */
    public function testImplementsCountable()
    {
        $this->assertContains(
            'Countable', class_implements(get_class($this->connections))
        );
    }

    /**
     * Tests that the class implements the IteratorAggregate interface.
     *
     * @return void
     */
    public function testImplementsIteratorAggregate()
    {
        $this->assertContains(
            'IteratorAggregate', class_implements(get_class($this->connections))
        );
    }

    /**
     * Tests adding a connection.
     *
     * @return void
     * @depends testImplementsCountable
     * @depends testImplementsIteratorAggregate
     */
    public function testAddConnection()
    {
        $connection = $this->getMockConnection();

        $this->assertEquals(0, count($this->connections));
        $this->connections->addConnection($connection);
        $this->assertEquals(1, count($this->connections));

        foreach ($this->connections as $entry) {
            $this->assertSame($connection, $entry);
        }
    }

    /**
     * Tests removing a connection by specifying the connection instance.
     *
     * @return void
     * @depends testAddConnection
     */
    public function testRemoveConnectionByInstance()
    {
        $connection = $this->getMockConnection();
        $this->connections->addConnection($connection);
        $this->connections->removeConnection($connection);
        $this->assertEquals(0, count($this->connections));
    }

    /**
     * Tests removing a connection by specifying the connection hostmask
     * when the connection is present.
     *
     * @return void
     * @depends testAddConnection
     */
    public function testRemoveConnectionByHostmaskWithConnectionPresent()
    {
        $connection = $this->getMockConnection();
        $this->connections->addConnection($connection);
        $this->connections->removeConnection((string) $connection->getHostmask());
        $this->assertEquals(0, count($this->connections));
    }

    /**
     * Tests that removing a connection by specifying the connection
     * hostmask when the connection is not present.
     *
     * @return void
     * @depends testAddConnection
     */
    public function testRemoveConnectionByHostmaskWithConnectionAbsent()
    {
        $this->connections->removeConnection('foo');
    }

    /**
     * Tests retrieving a list of connections when none have been added.
     *
     * @return void
     */
    public function testGetConnectionsWithNoConnections()
    {
        $this->assertSame(array(), $this->connections->getConnections());
    }

    /**
     * Returns a mock hostmask instance.
     *
     * @param string $nick     User's nickname
     * @param string $username User's username
     * @param string $host     User's hostname
     *
     * @return Phergie_Hostmask
     */
    protected function getMockHostmask($nick, $username, $host)
    {
        $hostmask = $this->getMock(
            'Phergie_Hostmask', array('__toString'), array($nick, $username, $host)
        );
        $hostmask
            ->expects($this->any())
            ->method('__toString')
            ->will($this->returnValue($nick . '!' . $username . '@' . $host));
        return $hostmask;
    }

    /**
     * Tests retrieving a single connection by its hostmask.
     *
     * @return void
     */
    public function testGetConnectionsWithSingleConnection()
    {
        $hostmask = $this->getMockHostmask('nick', 'username', 'host');
        $hostmaskString = (string) $hostmask;

        $connection = $this->getMockConnection();
        $connection
            ->expects($this->any())
            ->method('getHostmask')
            ->will($this->returnValue($hostmask));

        $this->connections->addConnection($connection);
        $connections = $this->connections->getConnections($hostmaskString);
        $this->assertInternalType('array', $connections);
        $this->assertSame(1, count($connections));
        $this->assertArrayHasKey($hostmaskString, $connections);
        $this->assertSame($connection, $connections[$hostmaskString]);
    }

    /**
     * Tests retrieving multiple connections by their hostmasks.
     *
     * @return void
     */
    public function testGetConnectionsWithMultipleConnections()
    {
        $hostmasks = $hostmaskStrings = $connections = array();
        $connection = $this->getMockConnection();
        foreach (range(1, 2) as $index) {
            $hostmasks[$index] = $this->getMockHostmask(
                'nick' . $index, 'username' . $index, 'host' . $index
            );
            $hostmaskStrings[$index] = (string) $hostmasks[$index];
            $connections[$index] = clone $connection;
            $connections[$index]
                ->expects($this->any())
                ->method('getHostmask')
                ->will($this->returnValue($hostmasks[$index]));
            $this->connections->addConnection($connections[$index]);
        }
        $returned = $this->connections->getConnections($hostmaskStrings);
        $this->assertInternalType('array', $returned);
        $this->assertEquals(2, count($returned));
        foreach ($hostmaskStrings as $index => $hostmaskString) {
            $this->assertArrayHasKey($hostmaskString, $returned);
            $this->assertSame($connections[$index], $returned[$hostmaskString]);
        }
    }
}
