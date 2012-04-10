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
 * @copyright 2008-2011 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Tests
 */

/**
 * Unit test suite for Phergie_Hostmask.
 *
 * @category Phergie
 * @package  Phergie_Tests
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Tests
 */
class Phergie_HostmaskTest extends PHPUnit_Framework_TestCase
{
    /**
     * Phergie_Hostmask instance for testing.
     *
     * @var Phergie_Hostmask $hostmask
     */
    private $hostmask;

    /**
     * Creates testing instance.
     *
     * @return void
     */
    public function setUp()
    {
        $this->hostmask = new Phergie_Hostmask('nick', 'username', 'host');
    }

    /**
     * TODO: Desc
     *
     * @return array TODO desc
     */
    public static function isValidProvider()
    {
        return array(
                array('nick!username@host', true),
                array('blech', false),
                array('!*@*', false),
                array('*!@*', false),
                array('*!*@', false)
        );
    }

    /**
     * Tests static method Phergie_Hostmask::isValid() with various valid and invalid
     * hostmasks.
     *
     * @dataProvider isValidProvider
     *
     * @return void
     */
    public function testIsValid($hostmask, $result)
    {
        $this->assertEquals($result, Phergie_HOstmask::isValid($hostmask));
    }

    /**
     * Tests static function ::fromString() to ensure
     * Nick, Username and Host properties are set correctly.
     *
     * @return voic
     */
    public function testFromString()
    {
        $hostmask = Phergie_Hostmask::fromString('nick!user@host');
        $this->assertEquals('nick', $hostmask->getNick());
        $this->assertEquals('user', $hostmask->getUsername());
        $this->assertEquals('host', $hostmask->getHost());
    }

    /**
     * Tests static function ::fromString() with invalid hostmask
     * to ensure proper exception is thrown.
     *
     * @return void
     */
    public function testFromStringWithInvalidHostmask()
    {
        $badstring = 'sdf982u19f92($&#@';
        try {
            $hostmask = Phergie_Hostmask::fromString($badstring);
            $this->fail(
                'Phergie_Hostmask::fromString didn\'t '
                . 'throw a required exception on a bad hostmask'
            );
        } catch (Phergie_Hostmask_Exception $phe) {
            $this->assertEquals(
                Phergie_Hostmask_Exception::ERR_INVALID_HOSTMASK, $phe->getCode()
            );
            $this->assertContains($badstring, $phe->getMessage());
        } catch (Exception $e) {
            $this->fail(
                'Phergie_Hostmask::fromString didn\'t throw the required exception'
            );
        }
    }

    /**
     * Tests getHost() function for returning correct default value.
     *
     * @return void
     */
    public function testGetHost()
    {
        $this->assertEquals('host', $this->hostmask->getHost());
    }

    /**
     * Tests setHost() function for correctly setting host property.
     *
     * @return void
     */
    public function testSetHost()
    {
        $this->hostmask->setHost('newhost');
        $this->assertEquals('newhost', $this->hostmask->getHost());
    }

    /**
     * Tests getUsername() function for returning correct default value.
     *
     * @return void
     */
    public function testGetUsername()
    {
        $this->assertEquals('username', $this->hostmask->getUsername());
    }

    /**
     * Tests setUsername() function for correctly setting username property.
     *
     * @return void
     */
    public function testSetUsername()
    {
        $this->hostmask->setUsername('newusername');
        $this->assertEquals('newusername', $this->hostmask->getUsername());
    }

    /**
     * Tests getNick() function for returning default value.
     *
     * @return void
     */
    public function testGetNick()
    {
        $this->assertEquals('nick', $this->hostmask->getNick());
    }

    /**
     * Tests setNick() function for correctly setting nick property.
     *
     * @return void
     */
    public function testSetNick()
    {
        $this->hostmask->setNick('newnickname');
        $this->assertEquals('newnickname', $this->hostmask->getNick());
    }

    /**
     * Tests magic __toString function for creating valid correct hostmask.
     *
     * @return void
     */
    public function test__toString()
    {
        $this->assertEquals('nick!username@host', $this->hostmask->__toString());
    }

    /**
     * Tests matches() function to match a pattern with a default hostmask.
     *
     * @return void
     */
    public function testMatchesTrueWithDefaultHostmask()
    {
        $myPattern = 'nick!username@host';
        $this->assertTrue($this->hostmask->matches($myPattern));
    }

    /**
     * Tests matches() function to match a pattern with a hostmask specified.
     *
     * @return void
     */
    public function testMatchesTrueWithHostmaskSpecified()
    {
        $myPattern = '1nick!1username@1host';
        $myHostmask = '1nick!1username@1host';
        $this->assertTrue($this->hostmask->matches($myPattern, $myHostmask));
    }

    /**
     * Tests matches() function for returning false when pattern does not match
     * the default hostmask.
     *
     * @return void
     */
    public function testMatchesFalseWithDefaultHostmask()
    {
        $myPattern = 'safoj!asoj@asfoh';
        $this->assertFalse($this->hostmask->matches($myPattern));
    }

    /**
     * Tests matches() function for returning false when pattern does not match
     * a specified hostmask.
     *
     * @return void
     */
    public function testMatchesFalseWithHostmaskSpecified()
    {
        $myPattern = '1nick!1username@1host';
        $myHostmask = '2nick!2username@2host';
        $this->assertFalse($this->hostmask->matches($myPattern, $myHostmask));
    }
}
