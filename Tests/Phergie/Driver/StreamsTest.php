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

        $this->driver->setConnection($this->getMockConnection());
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

    /**
     * Supporting method that tests that a given method call with a given
     * set of arguments produces a given command in the data sent by the
     * driver.
     *
     * @param string $command Command to check for in the driver's output
     * @param string $method  Name of the method to call
     * @param array  $args    Optional arguments to pass to the method when
     *                        calling it
     *
     * @return void
     */
    private function doCommandTest($command, $method, array $args = array())
    {
        $this->server->get();
        $this->server->run();

        $this->driver->setConnection($this->getMockConnection());
        $this->driver->doConnect();
        call_user_func_array(array($this->driver, $method), $args);
        if ($method != 'doQuit') {
            $this->driver->doQuit();
        }
        $this->server->close();

        $expected = "\r\n" . $command . "\r\n";
        $this->assertContains($expected, $this->server->getInput());
    }

    /**
     * Tests sending a QUIT command with a reason.
     *
     * @return void
     * @depends testSetConnectionWithNewConnection
     * @depends testDoConnectWithoutPassword
     */
    public function testDoQuitWithReason()
    {
        $this->doCommandTest('QUIT', 'doQuit');
    }

    /**
     * Tests sending a JOIN command without channel keys.
     *
     * @return void
     * @depends testSetConnectionWithNewConnection
     * @depends testDoConnectWithoutPassword
     */
    public function testDoJoinWithoutKeys()
    {
        $this->doCommandTest('JOIN :#channel', 'doJoin', array('#channel'));
    }

    /**
     * Tests sending a JOIN command with channel keys.
     *
     * @return void
     * @depends testSetConnectionWithNewConnection
     * @depends testDoConnectWithoutPassword
     */
    public function testDoJoinWithKeys()
    {
        $this->doCommandTest('JOIN #channel :key', 'doJoin', array('#channel', 'key'));
    }

    /**
     * Tests sending a PART command.
     *
     * @return void
     * @depends testSetConnectionWithNewConnection
     * @depends testDoConnectWithoutPassword
     */
    public function testDoPart()
    {
        $this->doCommandTest('PART :#channel', 'doPart', array('#channel'));
    }

    /**
     * Tests sending a INVITE command.
     *
     * @return void
     * @depends testSetConnectionWithNewConnection
     * @depends testDoConnectWithoutPassword
     */
    public function testDoInvite()
    {
        $this->doCommandTest('INVITE nick :#channel', 'doInvite', array('nick', '#channel'));
    }

    /**
     * Tests sending a NAMES command.
     *
     * @return void
     * @depends testSetConnectionWithNewConnection
     * @depends testDoConnectWithoutPassword
     */
    public function testDoNames()
    {
        $this->doCommandTest('NAMES :#channel', 'doNames', array('#channel'));
    }

    /**
     * Tests sending a LIST command with a specific query.
     *
     * @return void
     * @depends testSetConnectionWithNewConnection
     * @depends testDoConnectWithoutPassword
     */
    public function testDoListWithChannels()
    {
        $this->doCommandTest('LIST :query', 'doList', array('query'));
    }

    /**
     * Tests sending a LIST command without a specific query.
     *
     * @return void
     * @depends testSetConnectionWithNewConnection
     * @depends testDoConnectWithoutPassword
     */
    public function testDoListWithoutChannels()
    {
        $this->doCommandTest('LIST', 'doList');
    }

    /**
     * Tests sending a TOPIC command without a topic.
     *
     * @return void
     * @depends testSetConnectionWithNewConnection
     * @depends testDoConnectWithoutPassword
     */
    public function testDoTopicWithoutTopic()
    {
        $this->doCommandTest('TOPIC :#channel', 'doTopic', array('#channel'));
    }

    /**
     * Tests sending a TOPIC command with a topic.
     *
     * @return void
     * @depends testSetConnectionWithNewConnection
     * @depends testDoConnectWithoutPassword
     */
    public function testDoTopicWithTopic()
    {
        $this->doCommandTest('TOPIC #channel :new topic', 'doTopic', array('#channel', 'new topic'));
    }

    /**
     * Tests sending a MODE command with only a target.
     *
     * @return void
     * @depends testSetConnectionWithNewConnection
     * @depends testDoConnectWithoutPassword
     */
    public function testDoModeWithOnlyTarget()
    {
        $this->doCommandTest('MODE :nick', 'doMode', array('nick'));
    }

    /**
     * Tests sending a MODE command with a target and mode.
     *
     * @return void
     * @depends testSetConnectionWithNewConnection
     * @depends testDoConnectWithoutPassword
     */
    public function testDoModeWithTargetAndMode()
    {
        $this->doCommandTest('MODE nick :+i', 'doMode', array('nick', '+i'));
    }

    /**
     * Tests sending a MODE command with a target and mode requiring an
     * additional parameter.
     *
     * @return void
     * @depends testSetConnectionWithNewConnection
     * @depends testDoConnectWithoutPassword
     */
    public function testDoModeWithTargetModeAndParameter()
    {
        $this->doCommandTest('MODE nick +i :param', 'doMode', array('nick', '+i', 'param'));
    }

    /**
     * Tests sending a NICK command.
     *
     * @return void
     * @depends testSetConnectionWithNewConnection
     * @depends testDoConnectWithoutPassword
     */
    public function testDoNick()
    {
        $this->doCommandTest('NICK :nick', 'doNick', array('nick'));
    }

    /**
     * Tests sending a WHOIS command.
     *
     * @return void
     * @depends testSetConnectionWithNewConnection
     * @depends testDoConnectWithoutPassword
     */
    public function testDoWhois()
    {
        $this->doCommandTest('WHOIS :nick', 'doWhois', array('nick'));
    }

    /**
     * Tests sending a PRIVMSG command.
     *
     * @return void
     * @depends testSetConnectionWithNewConnection
     * @depends testDoConnectWithoutPassword
     */
    public function testDoPrivmsg()
    {
        $this->doCommandTest('PRIVMSG nick :message text', 'doPrivmsg', array('nick', 'message text'));
    }

    /**
     * Tests sending a NOTICE command.
     *
     * @return void
     * @depends testSetConnectionWithNewConnection
     * @depends testDoConnectWithoutPassword
     */
    public function testDoNotice()
    {
        $this->doCommandTest('NOTICE nick :message text', 'doNotice', array('nick', 'message text'));
    }

    /**
     * Tests sending a KICK command without a reason.
     *
     * @return void
     * @depends testSetConnectionWithNewConnection
     * @depends testDoConnectWithoutPassword
     */
    public function testDoKickWithoutReason()
    {
        $this->doCommandTest('KICK nick :#channel', 'doKick', array('nick', '#channel'));
    }

    /**
     * Tests sending a KICK command with a reason.
     *
     * @return void
     * @depends testSetConnectionWithNewConnection
     * @depends testDoConnectWithoutPassword
     */
    public function testDoKickWithReason()
    {
        $this->doCommandTest('KICK nick #channel :reason text', 'doKick', array('nick', '#channel', 'reason text'));
    }

    /**
     * Tests sending a PONG command.
     *
     * @return void
     * @depends testSetConnectionWithNewConnection
     * @depends testDoConnectWithoutPassword
     */
    public function testDoPong()
    {
        $this->doCommandTest('PONG :irc.freenode.net', 'doPong', array('irc.freenode.net'));
    }

    /**
     * Tests sending a CTCP ACTION command.
     *
     * @return void
     * @depends testSetConnectionWithNewConnection
     * @depends testDoConnectWithoutPassword
     */
    public function testDoAction()
    {
        $this->doCommandTest('PRIVMSG nick :' . chr(1) . 'ACTION action text' . chr(1), 'doAction', array('nick', 'action text'));
    }

    /**
     * Tests sending a CTCP PING command.
     *
     * @return void
     * @depends testSetConnectionWithNewConnection
     * @depends testDoConnectWithoutPassword
     */
    public function testDoPing()
    {
        $this->doCommandTest('NOTICE nick :' . chr(1) . 'PING hash' . chr(1), 'doPing', array('nick', 'hash'));
    }

    /**
     * Tests sending a CTCP VERSION command without a version.
     *
     * @return void
     * @depends testSetConnectionWithNewConnection
     * @depends testDoConnectWithoutPassword
     */
    public function testDoVersionWithoutVersion()
    {
        $this->doCommandTest('NOTICE nick :' . chr(1) . 'VERSION' . chr(1), 'doVersion', array('nick'));
    }

    /**
     * Tests sending a CTCP VERSION command with a version.
     *
     * @return void
     * @depends testSetConnectionWithNewConnection
     * @depends testDoConnectWithoutPassword
     */
    public function testDoVersionWithVersion()
    {
        $this->doCommandTest('NOTICE nick :' . chr(1) . 'VERSION version' . chr(1), 'doVersion', array('nick', 'version'));
    }

    /**
     * Tests sending a CTCP TIME command without a time.
     *
     * @return void
     * @depends testSetConnectionWithNewConnection
     * @depends testDoConnectWithoutPassword
     */
    public function testDoTimeWithoutTime()
    {
        $this->doCommandTest('NOTICE nick :' . chr(1) . 'TIME' . chr(1), 'doTime', array('nick'));
    }

    /**
     * Tests sending a CTCP TIME command with a time.
     *
     * @return void
     * @depends testSetConnectionWithNewConnection
     * @depends testDoConnectWithoutPassword
     */
    public function testDoTimeWithTime()
    {
        $this->doCommandTest('NOTICE nick :' . chr(1) . 'TIME time' . chr(1), 'doTime', array('nick', 'time'));
    }

    /**
     * Tests sending a CTCP FINGER command without a finger string.
     *
     * @return void
     * @depends testSetConnectionWithNewConnection
     * @depends testDoConnectWithoutPassword
     */
    public function testDoFingerWithoutFingerString()
    {
        $this->doCommandTest('NOTICE nick :' . chr(1) . 'FINGER' . chr(1), 'doFinger', array('nick'));
    }

    /**
     * Tests sending a CTCP FINGER command with a finger string.
     *
     * @return void
     * @depends testSetConnectionWithNewConnection
     * @depends testDoConnectWithoutPassword
     */
    public function testDoFingerWithFingerString()
    {
        $this->doCommandTest('NOTICE nick :' . chr(1) . 'FINGER finger string' . chr(1), 'doFinger', array('nick', 'finger string'));
    }

    /**
     * Tests sending a raw command.
     *
     * @return void
     * @depends testSetConnectionWithNewConnection
     * @depends testDoConnectWithoutPassword
     */
    public function testDoRaw()
    {
        $this->doCommandTest('COMMAND :param', 'doRaw', array('COMMAND :param'));
    }
}
