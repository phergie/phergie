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
    private $hostmask;
    
    public function setUp()
    {
        $this->hostmask = new Phergie_Hostmask('nick', 'username', 'host');
    }
    
    public function testIsValidPassesOnValidHostmask()
    {
        $this->assertTrue(Phergie_Hostmask::isValid('nick!username@host'));
    }

    public function testIsValidFailsOnInvalidHostmask()
    {
        $this->assertFalse(Phergie_Hostmask::isValid('blech'));
    }

    public function testIsValidFailsOnInvalidHostmaskNoNick()
    {
        $this->assertFalse(Phergie_Hostmask::isValid('!*@*'));
    }

    public function testIsValidFailsOnInvalidHostmaskNoUsername()
    {
        $this->assertFalse(Phergie_Hostmask::isValid('*!@*'));
    }

    public function testIsValidFailsOnInvalidHostmaskNoHost()
    {
        $this->assertFalse(Phergie_Hostmask::isValid('*!*@'));
    }
    
    public function testFromString()
    {
        $hostmask = Phergie_Hostmask::fromString('nick!user@host');
        $this->assertEquals('nick', $hostmask->getNick());
        $this->assertEquals('user', $hostmask->getUsername());
        $this->assertEquals('host', $hostmask->getHost());
    }

    public function testFromStringWithInvalidHostmask()
    {
        $badstring = 'sdf982u19f92($&#@';
        try {
            $hostmask = Phergie_Hostmask::fromString($badstring);
            $this->fail('Phergie_Hostmask::fromString didn\'t throw a required exception on a bad hostmask');
        } catch (Phergie_Hostmask_Exception $phe) {
            $this->assertEquals(Phergie_Hostmask_Exception::ERR_INVALID_HOSTMASK, $phe->getCode());
            $this->assertContains($badstring, $phe->getMessage());
        } catch (Exception $e) {
            $this->fail('Phergie_Hostmask::fromString didn\'t throw the required exception');
        }
    }

    public function testGetHost()
    {
        
        $this->assertEquals('host', $this->hostmask->getHost());
    }

    public function testSetHost()
    {
        $this->hostmask->setHost('newhost');
        $this->assertEquals('newhost', $this->hostmask->getHost());
    }

    public function testGetUsername()
    {
        $this->assertEquals('username', $this->hostmask->getUsername());
    }

    public function testSetUsername()
    {
        $this->hostmask->setUsername('newusername');
        $this->assertEquals('newusername', $this->hostmask->getUsername());
    }

    public function testGetNick()
    {
        $this->assertEquals('nick', $this->hostmask->getNick());
    }

    public function testSetNick()
    {
        $this->hostmask->setNick('newnickname');
        $this->assertEquals('newnickname', $this->hostmask->getNick());
    }

    public function test__toString()
    {
        $this->assertEquals('nick!username@host', $this->hostmask->__toString());
    }

    public function testMatchesTrueWithDefaultHostmask()
    {
        $myPattern = 'nick!username@host';
        $this->assertTrue($this->hostmask->matches($myPattern));
    }

    public function testMatchesTrueWithHostmaskSpecified()
    {
        $myPattern = '1nick!1username@1host';
        $myHostmask = '1nick!1username@1host';
        $this->assertTrue($this->hostmask->matches($myPattern, $myHostmask));
    }

    public function testMatchesFalseWithDefaultHostmask()
    {
        $myPattern = 'safoj!asoj@asfoh';
        $this->assertFalse($this->hostmask->matches($myPattern));
    }

    public function testMatchesFalseWithHostmaskSpecified()
    {
        $myPattern = '1nick!1username@1host';
        $myHostmask = '2nick!2username@2host';
        $this->assertFalse($this->hostmask->matches($myPattern, $myHostmask));
    }
}
