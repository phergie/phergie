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
 * Unit test suite for Phergie_Driver_Streams.
 *
 * @category Phergie
 * @package  Phergie_Tests
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Tests
 */
class Phergie_Driver_StreamsTest extends Phergie_TestCase
{
    /**
     * Instance of the class to test
     *
     * @var Phergie_Driver_Streams
     */
    private $driver;

    /**
     * Fake daemon process acting as an IRC server to test the driver
     *
     * @var Phergie_FakeDaemon
     */
    private $server;

    /**
     * Instantiates the class to test and its mock dependencies.
     *
     * @return void
     */
    public function setUp()
    {
        $this->server = new Phergie_FakeDaemon;
        $this->driver = new Phergie_Driver_Streams;
    }

    /**
     * Returns a mock connection.
     *
     * @return Phergie_Connection
     */
    protected function getMockConnection()
    {
        $options = array(
            'host'      => '0.0.0.0',
            'port'      => $this->server->getPort(),
            'username'  => 'username',
            'realname'  => 'realname',
            'transport' => 'tcp'
        );
        $connection = parent::getMockConnection();
        foreach ($options as $key => $value) {
            $connection
                ->expects($this->any())
                ->method('get' . ucfirst($key))
                ->will($this->returnValue($value));
        }
        return $connection;
    }

    /**
     * Tests that a default socket timeout is used if none is set.
     *
     * @return void
     */
    public function testGetTimeout()
    {
        $this->assertSame(0.1, $this->driver->getTimeout());
    }

    /**
     * Tests that a custom socket timeout can be set.
     *
     * @return void
     */
    public function testSetTimeout()
    {
        $timeout = 0.2;
        $this->driver->setTimeout($timeout);
        $this->assertequals($timeout, $this->driver->getTimeout());
    }

    /**
     * Tests that attempting to retrieve a connection before one is set
     * results in an exception.
     *
     * @return void
     */
    public function testGetConnection()
    {
        try {
            $this->driver->getConnection();
            $this->fail('Expected exception was not thrown');
        } catch (Phergie_Driver_Exception $e) {
            if ($e->getCode() != Phergie_Driver_Exception::ERR_NO_ACTIVE_CONNECTION) {
                $this->fail('Unexpected exception code: ' . $e->getCode());
            }
        }
    }

    /**
     * Tests that a new connection can be set and retrieved.
     *
     * @return void
     */
    public function testSetConnectionWithNewConnection()
    {
        $connection = $this->getMockConnection();
        $this->driver->setConnection($connection);
        $this->assertSame($connection, $this->driver->getConnection());
    }

    /**
     * Tests connecting to a server without a password.
     *
     * @return void
     * @depends testSetConnectionWithNewConnection
     */
    public function testDoConnectWithoutPassword()
    {
        $this->server->get();
        $this->server->run();

        $connection = $this->getMockConnection();
        $this->driver->setConnection($connection);
        $this->driver->doConnect();
        $this->driver->doQuit();
        $this->server->close();

        $expected =
            sprintf(
                'USER %s %s %s %s',
                $connection->getUsername(),
                $connection->getHost(),
                $connection->getHost(),
                ':' . $connection->getRealname()
            ) . "\r\n"
            . 'NICK :' . $connection->getNick() . "\r\n"
            . 'QUIT' . "\r\n";

        $this->assertSame($expected, $this->server->getInput());
    }

    /**
     * Tests connecting to a server with a password.
     *
     * @return void
     * @depends testSetConnectionWithNewConnection
     */
    public function testDoConnectWithPassword()
    {
        $this->server->get();
        $this->server->run();

        $connection = $this->getMockConnection();
        $connection
            ->expects($this->any())
            ->method('getPassword')
            ->will($this->returnValue('password'));

        $this->driver->setConnection($connection);
        $this->driver->doConnect();
        $this->driver->doQuit();
        $this->server->close();

        $expected =
            'PASS :' . $connection->getPassword() . "\r\n"
            . sprintf(
                'USER %s %s %s :%s',
                $connection->getUsername(),
                $connection->getHost(),
                $connection->getHost(),
                $connection->getRealname()
            ) . "\r\n"
            . 'NICK :' . $connection->getNick() . "\r\n"
            . 'QUIT' . "\r\n";

        $this->assertSame($expected, $this->server->getInput());
    }

    /**
     * Tests that an exception is thrown if a socket error occurs when
     * attempting to connect to a server.
     *
     * @return void
     */
    public function testDoConnectHandlesSocketException()
    {
        $timeout = ini_get('default_socket_timeout');
        ini_set('default_socket_timeout', 1);

        $this->driver->setConnection($this->getMockConnection());

        try {
            $this->driver->doConnect();
            $this->fail('Expected exception was not thrown');
        } catch (Phergie_Driver_Exception $e) {
            if ($e->getCode() != Phergie_Driver_Exception::ERR_CONNECTION_ATTEMPT_FAILED) {
                $this->fail('Unexpected exception code: ' . $e->getCode());
            }
        }

        ini_set('default_socket_timeout', $timeout);
    }

    /**
     * Tests that the client attempts to recover if a partial command is
     * sent to the server.
     *
     * @return void
     */
    public function testSendHandlesPartialWrite()
    {
        $this->server->get(4);
        $this->server->sleep(2);
        $this->server->get();
        $this->server->run();

        $connection = $this->getMockConnection();
        $this->driver->setConnection($connection);
        $this->driver->doConnect();
        $this->driver->doQuit();
        $this->server->close();
    }

    /**
     * Tests that a connection can be made the active connection following
     * its initial establishment.
     *
     * @return void
     * @depends testSetConnectionWithNewConnection
     * @depends testDoConnectWithoutPassword
     */
    public function testSetConnectionWithExistingConnection()
    {
        $this->server->get();
        $this->server->run();

        $connection = $this->getMockConnection();
        $this->driver->setConnection($connection);
        $this->driver->doConnect();
        $this->driver->setConnection($connection);
        $this->driver->doQuit();
        $this->server->close();
    }

    /**
     * Tests that an attempt to send a command without an active connection
     * results in an exception.
     *
     * @return void
     */
    public function testSendWithoutConnectionThrowsException()
    {
        try {
            $this->driver->doQuit();
            $this->fail('Expected exception not thrown');
        } catch (Phergie_Driver_Exception $e) {
            if ($e->getCode() != Phergie_Driver_Exception::ERR_NO_ACTIVE_CONNECTION) {
                $this->fail('Unexpected exception code: ' . $e->getCode());
            }
        }
    }
}
