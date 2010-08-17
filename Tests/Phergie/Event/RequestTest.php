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
 * Unit test suite for Phergie_Event_Request.
 *
 * @category Phergie
 * @package  Phergie_Tests
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Tests
 */
class Phergie_Event_RequestTest extends PHPUnit_Framework_TestCase
{
    /**
     * Instance of the class to test
     *
     * @var Phergie_Event_Request
     */
    private $event;

    /**
     * Instantiates the class to test.
     *
     * @return void
     */
    public function setUp()
    {
        $this->event = new Phergie_Event_Request;
    }

    /**
     * Tests that an attempt to retrieve the event hostmask when none has
     * been set results in an exception.
     *
     * @return void
     */
    public function testGetHostmask()
    {
        try {
            $this->event->getHostmask();
            $this->fail('Expected exception not thrown');
        } catch (Phergie_Event_Exception $e) {
            if ($e->getCode() != Phergie_Event_Exception::ERR_MISSING_HOSTMASK) {
                $this->fail('Unexpected exception code ' . $e->getCode());
            }
        }
    }

    /**
     * Tests that the hostmask associated with the event can be changed.
     *
     * @return void
     */
    public function testSetHostmask()
    {
        $hostmask = $this->getMock(
            'Phergie_Hostmask',
            array(),
            array('nick', 'username', 'host')
        );
        $this->event->setHostmask($hostmask);
        $this->assertSame($hostmask, $this->event->getHostmask());
    }

    /**
     * Tests that the event contains no arguments by default.
     *
     * @return void
     */
    public function testGetArguments()
    {
        $expected = array();
        $actual = $this->event->getArguments();
        $this->assertSame($expected, $actual);
    }

    /**
     * Tests that event arguments cannot be retrieved before an event type
     * is set.
     *
     * @return void
     */
    public function testGetArgumentWithoutEventType()
    {
        try {
            $this->event->getArgument('text');
            $this->fail('Expected exception not thrown');
        } catch (Phergie_Event_Exception $e) {
            if ($e->getCode() != Phergie_Event_Exception::ERR_INVALID_ARGUMENT) {
                $this->fail('Unexpected exception code ' . $e->getCode());
            }
        }
    }

    /**
     * Tests that an event argument cannot be retrieved if it does not
     * correspond to the specified event type.
     *
     * @return void
     */
    public function testGetArgumentWithInvalidArgument()
    {
        $this->event->setType('privmsg');
        try {
            $this->event->getArgument('message');
            $this->fail('Expected exception not thrown');
        } catch (Phergie_Event_Exception $e) {
            if ($e->getCode() != Phergie_Event_Exception::ERR_INVALID_ARGUMENT) {
                $this->fail('Unexpected exception code ' . $e->getCode());
            }
        }
    }

    /**
     * Tests that a default value is returned if an event argument has not
     * been assigned one.
     *
     * @return void
     */
    public function testGetArgumentWithUnsetArgument()
    {
        $this->event->setType('privmsg');
        $this->assertNull($this->event->getArgument('text'));
    }

    /**
     * Data provider for testSetArgumentWithValidArgument().
     *
     * @return array Enumerated array of enumerated arrays each containing
     *         parameter values for a single call to
     *         testSetArgumentWithValidArgument()
     */
    public function dataProviderTestSetArgumentWithValidArgument()
    {
        return array(
            array(0, 0, '#channel'),
            array('receiver', 0, '#channel'),
            array(1, 1, '#channel'),
            array('text', 1, '#channel'),
        );
    }

    /**
     * Tests that a single valid event argument can be changed.
     *
     * @param mixed  $argument Positional integer or string identifying the
     *        argument to set
     * @param int    $index    Positional integer corresponding to $argument
     * @param string $value    Argument value to assign
     *
     * @return void
     * @dataProvider dataProviderTestSetArgumentWithValidArgument
     */
    public function testSetArgumentWithValidArgument($argument,
        $index, $value
    ) {
        $this->event->setType('privmsg');
        $this->event->setArgument($argument, $value);
        $this->assertSame($value, $this->event->getArgument($argument));
    }

    /**
     * Tests that an event argument cannot be set without first setting a
     * valid event type.
     *
     * @return void
     */
    public function testSetArgumentWithoutEventType()
    {
        try {
            $this->event->setArgument('receiver', '#channel');
            $this->fail('Expected exception not thrown');
        } catch (Phergie_Event_Exception $e) {
            if ($e->getCode() != Phergie_Event_Exception::ERR_INVALID_ARGUMENT) {
                $this->fail('Unexpected exception code ' . $e->getCode());
            }
        }
    }

    /**
     * Tests that an event argument cannot be set if it does not correspond
     * to the specified event type.
     *
     * @return void
     */
    public function testSetArgumentWithInvalidArgument()
    {
        $this->event->setType('privmsg');
        try {
            $this->event->setArgument('message', 'foo');
            $this->fail('Expected exception not thrown');
        } catch (Phergie_Event_Exception $e) {
            if ($e->getCode() != Phergie_Event_Exception::ERR_INVALID_ARGUMENT) {
                $this->fail('Unexpected exception code ' . $e->getCode());
            }
        }
    }

    /**
     * Tests that the event arguments can be changed.
     *
     * @return void
     * @depends testSetArgumentWithValidArgument
     */
    public function testSetArguments()
    {
        $this->event->setType('privmsg');
        $expected = array('#channel', 'text');
        $this->event->setArguments($expected);
        $actual = $this->event->getArguments();
        $this->assertSame($expected, $actual);
    }

    /**
     * Tests that event arguments can be removed.
     *
     * @return void
     * @depends testSetArgumentWithValidArgument
     */
    public function testRemoveArgument()
    {
        $this->event->setType('privmsg');
        $this->event->setArgument('receiver', '#channel');
        $this->event->removeArgument('receiver');
        $this->assertNull($this->event->getArgument('receiver'));
    }

    /**
     * Tests that no raw data is associated with the event by default.
     *
     * @return void
     */
    public function testGetRawData()
    {
        $this->assertNull($this->event->getRawData());
    }

    /**
     * Tests that the raw data associated with the event can be changed.
     *
     * @return void
     */
    public function testSetRawData()
    {
        $expected = 'foo';
        $this->event->setRawData($expected);
        $actual = $this->event->getRawData();
        $this->assertSame($expected, $actual);
    }

    /**
     * Returns a hostmask instance configured for a specified nick.
     *
     * @param string $nick     Optional user nick to associate with the
     *        hostmask, defaults to 'nick'
     * @param string $username Optional username to associate with the
     *        hostmask, defaults to 'username'
     *
     * @return Phergie_Hostmask
     */
    private function getMockHostmask($nick = 'nick', $username = 'username')
    {
        $hostmask = $this->getMock(
            'Phergie_Hostmask',
            array('getNick', 'getUsername'),
            array($nick, 'username', 'example.com')
        );
        $hostmask
            ->expects($this->any())
            ->method('getNick')
            ->will($this->returnValue($nick));
        $hostmask
            ->expects($this->any())
            ->method('getUsername')
            ->will($this->returnValue($username));
        return $hostmask;
    }

    /**
     * Tests that the nick of the user who originated the event can be
     * retrieved.
     *
     * @return void
     * @depends testSetHostmask
     */
    public function testGetNick()
    {
        $expected = 'nick';
        $hostmask = $this->getMockHostmask($expected);
        $this->event->setHostmask($hostmask);
        $actual = $this->event->getNick();
        $this->assertSame($expected, $actual);
    }

    /**
     * Tests that the channel name is returned as the event source if the
     * event occurs in a channel.
     *
     * @return void
     */
    public function testGetSourceWithChannel()
    {
        $expected = '#channel';
        $this->event->setType('privmsg');
        $this->event->setArguments(array($expected, 'text'));
        $actual = $this->event->getSource();
        $this->assertSame($expected, $actual);
    }

    /**
     * Tests that the user nick is returned as the event source if the event
     * is a private message to the bot from a user.
     *
     * @return void
     */
    public function testGetSourceWithUser()
    {
        $expected = 'nick';
        $hostmask = $this->getMockHostmask($expected);
        $this->event->setHostmask($hostmask);
        $this->event->setType('privmsg');
        $this->event->setArguments(array($expected, 'text'));
        $actual = $this->event->getSource();
        $this->assertSame($expected, $actual);
    }

    /**
     * Data provider for testIsInChannel().
     *
     * @return array Enumerated array of enumerated arrays each containing
     *         parameter values for a single call to testIsInChannel()
     */
    public function dataProviderTestIsInChannel()
    {
        return array(
            array('#channel', true),
            array('nick', false),
        );
    }

    /**
     * Tests that the event properly detects whether it occurred within a
     * channel.
     *
     * @param string  $source      Event source
     * @param boolean $isInChannel TRUE if the event occurred in a channel,
     *        FALSE otherwise
     *
     * @return void
     * @depends testGetSourceWithChannel
     * @depends testGetSourceWithUser
     * @dataProvider dataProviderTestIsInChannel
     */
    public function testIsInChannel($source, $isInChannel)
    {
        $hostmask = $this->getMockHostmask('nick');
        $this->event->setHostmask($hostmask);
        $this->event->setType('privmsg');
        $this->event->setArguments(array($source, 'text'));
        $this->assertSame($isInChannel, $this->event->isInChannel());
    }

    /**
     * Data provider for testIsFromUser().
     *
     * @return array Enumerated array of enumerated arrays each containing
     *         parameter values for a single call to testIsFromUser()
     */
    public function dataProviderTestIsFromUser()
    {
        return array(
            array(null, null, false),
            array('nick', 'username', true),
        );
    }

    /**
     * Tests that the event properly detects whether it occurred within a
     * private message from a user.
     *
     * @param mixed   $nick       String containing the user's nick or NULL
     *        if the event is not from a user
     * @param mixed   $username   String containing the user's username or
     *        NULL if the event is not from a user
     * @param boolean $isFromUser TRUE if the event is from a user, FALSE
     *        otherwise
     *
     * @return void
     * @dataProvider dataProviderTestIsFromUser
     */
    public function testIsFromUser($nick, $username, $isFromUser)
    {
        $hostmask = $this->getMockHostmask($nick, $username);
        $this->event->setHostmask($hostmask);
        $this->assertSame($isFromUser, $this->event->isFromUser());
    }

    /**
     * Data provider for testIsFromServer().
     *
     * @return array Enumerated array of enumerated arrays each containing
     *         parameter values for a single call to testIsFromServer()
     */
    public function dataProviderTestIsFromServer()
    {
        return array(
            array(null, null, true),
            array('nick', 'username', false),
        );
    }

    /**
     * Tests that the event properly detects whether the server (versus a
     * user) originated it.
     *
     * @param mixed   $nick         String containing the user's nick or NULL
     *        if the event is not from a user
     * @param mixed   $username     String containing the user's username or
     *        NULL if the event is not from a user
     * @param boolean $isFromServer TRUE if the event is from the server,
     *        FALSE otherwise
     *
     * @return void
     * @dataProvider dataProviderTestIsFromServer
     */
    public function testIsFromServer($nick, $username, $isFromServer)
    {
        $hostmask = $this->getMockHostmask($nick, $username);
        $this->event->setHostmask($hostmask);
        $this->assertSame($isFromServer, $this->event->isFromServer());
    }

    /**
     * Tests that the event makes virtual "getter" methods available based
     * on the parameters associated with the event type.
     *
     * @return void
     */
    public function testProvidesVirtualGetterMethods()
    {
        $receiver = '#channel';
        $text = 'text';
        $this->event->setType('privmsg');
        $this->event->setArguments(array($receiver, $text));
        $this->assertSame($receiver, $this->event->getReceiver());
        $this->assertSame($text, $this->event->getText());
    }

    /**
     * Tests that calling an undefined method on the event results in an
     * exception.
     *
     * @return void
     */
    public function testInvalidMethodCallRaisesException()
    {
        try {
            $this->event->foo();
            $this->fail('Expected exception not thrown');
        } catch (Phergie_Event_Exception $e) {
            if ($e->getCode() != Phergie_Event_Exception::ERR_INVALID_METHOD_CALL) {
                $this->fail('Unexpected exception code ' . $e->getCode());
            }
        }
    }

    /**
     * Tests that the presence of event arguments can be checked using array
     * syntax.
     *
     * @return void
     */
    public function testImplementsOffsetExists()
    {
        $this->event->setType('privmsg');
        $this->assertFalse(isset($this->event['message']));
        $this->assertFalse(isset($this->event['text']));
        $this->event->setArgument('text', 'text');
        $this->assertTrue(isset($this->event['text']));
    }

    /**
     * Tests that event argument values can be retrieved using array syntax.
     *
     * @return void
     * @depends testSetArgumentWithValidArgument
     */
    public function testImplementsOffsetGet()
    {
        $receiver = '#channel';
        $this->event->setType('privmsg');
        $this->event->setArgument('receiver', $receiver);
        $this->assertSame($receiver, $this->event['receiver']);
    }

    /**
     * Tests that event argument values can be set using array syntax.
     *
     * @return void
     * @depends testSetArgumentWithValidArgument
     */
    public function testImplementsOffsetSet()
    {
        $receiver = '#channel';
        $this->event->setType('privmsg');
        $this->event['receiver'] = $receiver;
        $this->assertSame($receiver, $this->event->getArgument('receiver'));
    }

    /**
     * Tests that event argument values can be removed using array syntax.
     *
     * @return void
     * @depends testSetArgumentWithValidArgument
     */
    public function testImplementsOffsetUnset()
    {
        $this->event->setType('privmsg');
        $this->event->setArgument('receiver', '#channel');
        unset($this->event['receiver']);
        $this->assertNull($this->event->getArgument('receiver'));
    }
}
