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
     * List of sockets in use by the driver
     *
     * @var array
     */
    protected $sockets;

    /**
     * Instantiates the class to test and its mock dependencies.
     *
     * @return void
     */
    public function setUp()
    {
        $this->sockets = array();
        $this->driver = $this->getMock(
            'Phergie_Driver_Streams', array('write', 'connect')
        );
        $this->driver
            ->expects($this->any())
            ->method('connect')
            ->will($this->returnCallback(array($this, 'createSocket')));
    }

    /**
     * Callback for creating a new client socket connection.
     *
     * @return resource Stream socket
     */
    public function createSocket()
    {
        $socket = fopen('php://temp', 'r+');
        $this->sockets[] = $socket;
        return $socket;
    }

    /**
     * Simulates a server by writing data to a specified socket for the
     * driver to read.
     *
     * @param int    $index Index of the socket in $this->sockets to
     *        receive the data
     * @param string $data  Data to write to the socket
     *
     * @return void
     */
    protected function writeEventToSocket($index, $data)
    {
        fwrite($this->sockets[$index], $data . "\r\n");
        rewind($this->sockets[$index]);
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
            'port'      => 6667,
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
            if (
                $e->getCode() != Phergie_Driver_Exception::ERR_NO_ACTIVE_CONNECTION
            ) {
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
     * Mocks driver method calls for sending commands.
     *
     * @param array $commands Associative array of commands mapping index
     *        (sequential order of calls beginning from 1) to their
     *        respective expected command
     *
     * @return void
     */
    protected function assertSendsCommands(array $commands)
    {
        foreach ($commands as $index => $command) {
            $this->driver
                ->expects($this->at($index))
                ->method('write')
                ->with($command . "\r\n")
                ->will($this->returnValue(strlen($command) + 2));
        }
    }

    /**
     * Mocks driver method calls to accept all write operations for sending
     * commands as successes.
     *
     * @return void
     */
    protected function acceptAllCommands()
    {
        $this->driver
            ->expects($this->any())
            ->method('write')
            ->will($this->returnCallback('strlen'));
    }

    /**
     * Tests connecting to a server without a password.
     *
     * @return void
     * @depends testSetConnectionWithNewConnection
     */
    public function testDoConnectWithoutPassword()
    {
        $connection = $this->getMockConnection();

        $this->assertSendsCommands(
            array(
                1 => sprintf(
                    'USER %s %s %s :%s',
                    $connection->getUsername(),
                    $connection->getHost(),
                    $connection->getHost(),
                    $connection->getRealname()
                ),
                2 => 'NICK :' . $connection->getNick(),
                3 => 'QUIT'
            )
        );

        $this->driver->setConnection($connection);
        $this->driver->doConnect();
        $this->driver->doQuit();
    }

    /**
     * Tests connecting to a server with a password.
     *
     * @return void
     * @depends testSetConnectionWithNewConnection
     */
    public function testDoConnectWithPassword()
    {
        $connection = $this->getMockConnection();
        $connection
            ->expects($this->any())
            ->method('getPassword')
            ->will($this->returnValue('password'));

        $this->assertSendsCommands(
            array(
                1 => 'PASS :' . $connection->getPassword(),
                2 => sprintf(
                    'USER %s %s %s :%s',
                    $connection->getUsername(),
                    $connection->getHost(),
                    $connection->getHost(),
                    $connection->getRealname()
                ),
                3 => 'NICK :' . $connection->getNick(),
                4 => 'QUIT'
            )
        );

        $this->driver->setConnection($connection);
        $this->driver->doConnect();
        $this->driver->doQuit();
    }

    /**
     * Tests that an exception is thrown if a socket error occurs when
     * attempting to connect to a server.
     *
     * @return void
     */
    public function testDoConnectHandlesSocketException()
    {
        $this->driver = $this->getMock('Phergie_Driver_Streams', array('connect'));
        $this->driver
            ->expects($this->any())
            ->method('connect')
            ->will($this->returnValue(false));

        $this->driver->setConnection($this->getMockConnection());

        try {
            $this->driver->doConnect();
            $this->fail('Expected exception was not thrown');
        } catch (Phergie_Driver_Exception $e) {
            if (
                $e->getCode() != Phergie_Driver_Exception::ERR_CONNECTION_ATTEMPT_FAILED
            ) {
                $this->fail('Unexpected exception code: ' . $e->getCode());
            }
        }
    }

    /**
     * Callback for testSendHandlesPartialWriteWithSuccess() to mock the
     * write() method of the driver. Uses a public access modifier to be
     * accessible as a callback.
     *
     * @param string $buffer Data to be written to the socket
     *
     * @return int Number of bytes written
     */
    public function sendHandlesPartialWriteWithSuccessCallback($buffer)
    {
        static $invocations = 0;

        if (++$invocations % 2 == 1) {
            return 4;
        } else {
            return strlen($buffer);
        }
    }

    /**
     * Tests that the client attempts to recover if a partial command is
     * sent to the server.
     *
     * @return void
     */
    public function testSendHandlesPartialWriteWithSuccess()
    {
        $this->driver
            ->expects($this->any())
            ->method('write')
            ->will(
                $this->returnCallback(
                    array($this, 'sendHandlesPartialWriteWithSuccessCallback')
                )
            );
        $this->driver->setConnection($this->getMockConnection());
        $this->driver->doConnect();
        $this->driver->doQuit();
    }

    /**
     * Tests that the client throws an exception when attempts to recover
     * when a partial command is sent to the server fail.
     *
     * @return void
     */
    public function testSendHandlesPartialWriteWithFailure()
    {
        $this->driver
            ->expects($this->any())
            ->method('write')
            ->will($this->returnValue(0));
        $this->driver->setConnection($this->getMockConnection());
        try {
            $this->driver->doConnect();
            $this->driver->doQuit();
            $this->fail('Expected exception was not thrown');
        } catch (Phergie_Driver_Exception $e) {
            if (
                $e->getCode() != Phergie_Driver_Exception::ERR_CONNECTION_WRITE_FAILED
            ) {
                $this->fail('Unexpected exception code: ' . $e->getCode());
            }
        }
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
        $this->acceptAllCommands();
        $connection = $this->getMockConnection();
        $this->driver->setConnection($connection);
        $this->driver->doConnect();
        $this->driver->setConnection($connection);
        $this->driver->doQuit();
    }

    /**
     * Tests that an attempt to send a command without an active connection
     * results in an exception.
     *
     * @return void
     */
    public function testSendWithoutConnectionThrowsException()
    {
        $this->acceptAllCommands();
        try {
            $this->driver->doQuit();
            $this->fail('Expected exception not thrown');
        } catch (Phergie_Driver_Exception $e) {
            if (
                $e->getCode() != Phergie_Driver_Exception::ERR_NO_ACTIVE_CONNECTION
            ) {
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
        $this->acceptAllCommands();
        $this->driver->setConnection($this->getMockConnection());

        $this->assertSendsCommands(array(
            3 => $command
        ));

        $this->driver->doConnect();
        call_user_func_array(array($this->driver, $method), $args);
        if ($method != 'doQuit') {
            $this->driver->doQuit();
        }
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
        $this->doCommandTest(
            'JOIN #channel :key', 'doJoin', array('#channel', 'key')
        );
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
        $this->doCommandTest(
            'PART :#channel', 'doPart', array('#channel')
        );
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
        $this->doCommandTest(
            'INVITE nick :#channel', 'doInvite', array('nick', '#channel')
        );
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
        $this->doCommandTest(
            'TOPIC #channel :new topic', 'doTopic', array('#channel', 'new topic')
        );
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
        $this->doCommandTest(
            'MODE nick +i :param', 'doMode', array('nick', '+i', 'param')
        );
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
        $this->doCommandTest(
            'PRIVMSG nick :message text', 'doPrivmsg', array('nick', 'message text')
        );
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
        $this->doCommandTest(
            'NOTICE nick :message text', 'doNotice', array('nick', 'message text')
        );
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
        $this->doCommandTest(
            'KICK nick :#channel', 'doKick', array('nick', '#channel')
        );
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
        $this->doCommandTest(
            'KICK nick #channel :reason text', 'doKick',
            array('nick', '#channel', 'reason text')
        );
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
        $this->doCommandTest(
            'PONG :irc.freenode.net', 'doPong', array('irc.freenode.net')
        );
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
        $this->doCommandTest(
            'PRIVMSG nick :' . chr(1) . 'ACTION action text'
            . chr(1), 'doAction', array('nick', 'action text')
        );
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
        $this->doCommandTest(
            'NOTICE nick :' . chr(1) . 'PING hash' . chr(1),
            'doPing', array('nick', 'hash')
        );
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
        $this->doCommandTest(
            'NOTICE nick :' . chr(1) . 'VERSION' . chr(1), 'doVersion', array('nick')
        );
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
        $this->doCommandTest(
            'NOTICE nick :' . chr(1) . 'VERSION version'
            . chr(1), 'doVersion', array('nick', 'version')
        );
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
        $this->doCommandTest(
            'NOTICE nick :' . chr(1) . 'TIME' . chr(1), 'doTime', array('nick')
        );
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
        $this->doCommandTest(
            'NOTICE nick :' . chr(1) . 'TIME time'
            . chr(1), 'doTime', array('nick', 'time')
        );
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
        $this->doCommandTest(
            'NOTICE nick :' . chr(1) . 'FINGER' . chr(1), 'doFinger', array('nick')
        );
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
        $this->doCommandTest(
            'NOTICE nick :' . chr(1) . 'FINGER finger string'
            . chr(1), 'doFinger', array('nick', 'finger string')
        );
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

    /**
     * Tests retrieving a list of active sockets when none have data available.
     *
     * @return void
     * @depends testSetConnectionWithNewConnection
     * @depends testDoConnectWithoutPassword
     */
    public function testGetActiveReadSocketsWithNoData()
    {
        $this->acceptAllCommands();
        $connection = $this->getMockConnection();
        $this->driver->setConnection($connection);
        $this->driver->doConnect();
        $this->driver->doQuit();
        $active = $this->driver->getActiveReadSockets();
        $this->assertEquals(0, count($active));
    }

    /**
     * Tests retrieving a list of active sockets when data is available for
     * reading.
     *
     * @return void
     * @depends testSetConnectionWithNewConnection
     * @depends testDoConnectWithoutPassword
     */
    public function testGetActiveReadSocketsWithData()
    {
        $this->acceptAllCommands();
        $connection = $this->getMockConnection();
        $this->driver->setConnection($connection);
        $this->driver->doConnect();
        $active = $this->driver->getActiveReadSockets();
        $this->driver->doQuit();
        $this->assertEquals(1, count($active));
    }

    /**
     * Tests attempting to get a new event on the active connection when no
     * data is available.
     *
     * @return void
     * @depends testSetConnectionWithNewConnection
     * @depends testDoConnectWithoutPassword
     */
    public function testGetEventWithNoData()
    {
        $this->acceptAllCommands();
        $connection = $this->getMockConnection();
        $this->driver->setConnection($connection);
        $this->driver->doConnect();
        $result = $this->driver->getEvent();
        $this->driver->doQuit();
        $this->assertNull($result);
    }

    /**
     * Data provider for testGetEventWithUserRequest().
     *
     * @return array Enumerated array of enumerated arrays each containing
     *         argument values for a single call to
     *         testGetEventWithUserRequest()
     */
    public function dataProviderTestGetEventWithUserRequest()
    {
        $null = chr(1);

        return array(
            array('NICK nickname', 'nick', array('nickname')),
            array('QUIT', 'quit', array()),
            array('QUIT reason', 'quit', array('reason')),
            array('PING :verne.freenode.net', 'ping', array('verne.freenode.net')),
            array('PONG :verne.freenode.net', 'pong', array('verne.freenode.net')),
            array('ERROR message', 'error', array('message')),
            array('PRIVMSG #channel :Think I got it.', 'privmsg', array('#channel', 'Think I got it.')),
            array('NOTICE * :*** Looking up your hostname...', 'notice', array('*', '*** Looking up your hostname...')),
            array('PRIVMSG #channel :' . $null . 'VERSION' . $null, 'version', array()),
            array('NOTICE #channel :' . $null . 'VERSION x.y.z' . $null, 'version', array('x.y.z')),
            array('PRIVMSG #channel :' . $null . 'TIME' . $null, 'time', array()),
            array('NOTICE #channel :' . $null . 'TIME 12345' . $null, 'time', array('12345')),
            array('PRIVMSG #channel :' . $null . 'FINGER' . $null, 'finger', array()),
            array('NOTICE #channel :' . $null . 'FINGER reply' . $null, 'finger', array('reply')),
            array('PRIVMSG #channel :' . $null . 'PING' . $null, 'ping', array()),
            array('NOTICE #channel :' . $null . 'PING reply' . $null, 'ping', array('reply')),
            array('PRIVMSG #channel :' . $null . 'ACTION tests something.' . $null, 'action', array('#channel', 'tests something.')),
            array('TOPIC #channel', 'topic', array('#channel')),
            array('TOPIC #channel topic', 'topic', array('#channel', 'topic')),
            array('PART #channel', 'part', array('#channel')),
            array('INVITE nickname #channel', 'invite', array('nickname', '#channel')),
            array('JOIN #channel', 'join', array('#channel')),
            array('JOIN #channel key', 'join', array('#channel', 'key')),
            array('KICK #channel user', 'kick', array('#channel', 'user')),
            array('KICK #channel user comment', 'kick', array('#channel', 'user', 'comment')),
            array('MODE #channel +i', 'mode', array('#channel', '+i')),
            array('MODE #channel +l 100', 'mode', array('#channel', '+l', '100')),
        );
    }

    /**
     * Tests reception of a event from a user.
     *
     * @param string $event     IRC event without the leading prefix
     * @param string $type      Event type
     * @param array  $arguments Event arguments
     *
     * @return void
     * @depends testSetConnectionWithNewConnection
     * @depends testDoConnectWithoutPassword
     * @dataProvider dataProviderTestGetEventWithUserRequest
     */
    public function testGetEventWithUserRequest($event, $type, $arguments)
    {
        $this->acceptAllCommands();
        $connection = $this->getMockConnection();
        $this->driver->setConnection($connection);
        $this->driver->doConnect();
        $this->writeEventToSocket(0, ':Elazar!~Elazar@yakko.itrebal.com ' . $event);

        $event = $this->driver->getEvent();
        $this->assertInstanceOf('Phergie_Event_Request', $event);
        $this->assertEquals($type, $event->getType());
        $this->assertEquals($arguments, $event->getArguments());

        $hostmask = $event->getHostmask();
        $this->assertInstanceOf('Phergie_Hostmask', $hostmask);
        $this->assertEquals('Elazar', $hostmask->getNick());
        $this->assertEquals('~Elazar', $hostmask->getUsername());
        $this->assertEquals('yakko.itrebal.com', $hostmask->getHost());
    }

    /**
     * Tests reception of a request event from a server.
     *
     * @return void
     * @depends testSetConnectionWithNewConnection
     * @depends testDoConnectWithoutPassword
     */
    public function testGetEventWithServerRequest()
    {
        $this->acceptAllCommands();
        $connection = $this->getMockConnection();
        $this->driver->setConnection($connection);
        $this->driver->doConnect();
        $this->writeEventToSocket(
            0, ':verne.freenode.net NOTICE * :*** Looking up your hostname...'
        );

        $event = $this->driver->getEvent();
        $this->assertInstanceOf('Phergie_Event_Request', $event);
        $this->assertEquals('notice', $event->getType());
        $this->assertEquals(
            array('*', '*** Looking up your hostname...'), $event->getArguments()
        );

        $hostmask = $event->getHostmask();
        $this->assertInstanceOf('Phergie_Hostmask', $hostmask);
        $this->assertNull($hostmask->getNick());
        $this->assertNull($hostmask->getUsername());
        $this->assertEquals('verne.freenode.net', $hostmask->getHost());
    }

    /**
     * Tests reception of a response event from a server.
     *
     * @return void
     * @depends testSetConnectionWithNewConnection
     * @depends testDoConnectWithoutPassword
     */
    public function testGetEventWithServerResponse()
    {
        $this->acceptAllCommands();
        $connection = $this->getMockConnection();
        $this->driver->setConnection($connection);
        $this->driver->doConnect();
        $this->writeEventToSocket(
            0, ':verne.freenode.net 376 Phergie :End of /MOTD command.'
        );

        $event = $this->driver->getEvent();
        $this->assertInstanceOf('Phergie_Event_Response', $event);
        $this->assertEquals('response', $event->getType());
        $this->assertEquals('376', $event->getCode());
        $this->assertEquals('End of /MOTD command.', $event->getDescription());
    }
}
