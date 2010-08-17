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
 * Unit test suite for Phergie_Connection.
 *
 * @category Phergie
 * @package  Phergie_Tests
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Tests
 */
class Phergie_ConnectionTest extends PHPUnit_Framework_TestCase
{
    /**
     * Associative array containing an option-to-value mapping
     *
     * @var array
     */
    private $options = array(
        'host' => 'example.com',
        'port' => 4080,
        'transport' => 'udp',
        'encoding' => 'ASCII',
        'nick' => 'MyNick',
        'username' => 'MyUsername',
        'realname' => 'MyRealName',
        'password' => 'MyPassword',
    );

    /**
     * Data provider for testGetOptionReturnsDefault().
     *
     * @return array Enumerated array of enumerated arrays each containing a
     *               set of parameters for a single call to
     *               testGetOptionReturnsDefault()
     */
    public function dataProviderTestGetOptionReturnsDefault()
    {
        return array(
            array('transport', 'tcp'),
            array('encoding', 'ISO-8859-1'),
            array('port', 6667),
            array('password', null),
        );
    }

    /**
     * Tests that a default values are used for some options.
     *
     * @param string $option Name of the option with a default value
     * @param mixed  $value  Default value of the option
     *
     * @return void
     * @dataProvider dataProviderTestGetOptionReturnsDefault
     */
    public function testGetOptionReturnsDefault($option, $value)
    {
        $connection = new Phergie_Connection;
        $this->assertEquals($value, $connection->{'get' . ucfirst($option)}());
    }

    /**
     * Tests that a default encoding is used if one isn't specified.
     *
     * @return void
     */
    public function testGetEncodingReturnsDefault()
    {
        $connection = new Phergie_Connection;
        $this->assertEquals('ISO-8859-1', $connection->getEncoding());
    }

    /**
     * Tests that options can be set via the constructor.
     *
     * @return void
     */
    public function testSetOptionsViaConstructor()
    {
        $connection = new Phergie_Connection($this->options);
        foreach ($this->options as $key => $value) {
            $this->assertEquals($value, $connection->{'get' . ucfirst($key)}());
        }
    }

    /**
     * Data provider for testGetHostmaskMissingDataGeneratesException().
     *
     * @return array Enumerated array of enumerated arrays each containing a
     *               set of parameters for a single call to
     *               testGetHostmaskMissingDataGeneratesException()
     */
    public function dataProviderTestGetHostmaskMissingDataGeneratesException()
    {
        return array(
            array(null, $this->options['username'], $this->options['host']),
            array($this->options['nick'], null, $this->options['host']),
            array($this->options['nick'], $this->options['username'], null),
        );
    }

    /**
     * Tests that attempting to retrieve a hostmask without option values
     * for all of its constituents generates an exception.
     *
     * @param string $nick     Bot nick
     * @param string $username Bot username
     * @param string $host     Server hostname
     *
     * @return void
     * @dataProvider dataProviderTestGetHostmaskMissingDataGeneratesException
     */
    public function testGetHostmaskMissingDataGeneratesException($nick, $username, $host)
    {
        $options = array(
            'nick' => $nick,
            'username' => $username,
            'host' => $host,
        );

        $connection = new Phergie_Connection($options);

        try {
            $hostmask = $connection->getHostmask();
            $this->fail('Expected exception was not thrown');
        } catch (Phergie_Connection_Exception $e) {
            return;
        } catch (Exception $e) {
            $this->fail('Unexpected exception was thrown');
        }
    }

    /**
     * Tests that attempting to retrieve a hostmask with all required
     * options is successful.
     *
     * @return void
     */
    public function testGetHostmaskWithValidData()
    {
        $options = array(
            'nick' => 'MyNick',
            'username' => 'MyUsername',
            'host' => 'example.com'
        );

        $connection = new Phergie_Connection($options);
        $hostmask = $connection->getHostmask();
        $this->assertType('Phergie_Hostmask', $hostmask);
    }

    /**
     * Data provider for testGetRequiredOptionsWithoutValuesSet().
     *
     * @return array Enumerated array of enumerated arrays each containing a
     *               set of parameters for a single call to
     *               testGetRequiredOptionsWithoutValuesSet()
     */
    public function dataProviderTestGetRequiredOptionsWithoutValuesSet()
    {
        return array(
            array('host'),
            array('nick'),
            array('username'),
            array('realname'),
        );
    }

    /**
     * Tests that attempting to retrieve values of required options when no
     * values are set results in an exception.
     *
     * @param string $option Option name
     *
     * @return void
     * @dataProvider dataProviderTestGetRequiredOptionsWithoutValuesSet
     */
    public function testGetRequiredOptionsWithoutValuesSet($option)
    {
        try {
            $connection = new Phergie_Connection;
            $value = $connection->{'get' . ucfirst($option)}();
            $this->fail('Expected exception was not thrown');
        } catch (Phergie_Connection_Exception $e) {
            return;
        } catch (Exception $e) {
            $this->fail('Unexpected exception was thrown');
        }
    }

    /**
     * Tests that attempting to set an invalid value for the transport
     * results in an exception.
     *
     * @return void
     */
    public function testSetTransportWithInvalidValue()
    {
        $connection = new Phergie_Connection;
        try {
            $connection->setTransport('blah');
            $this->fail('Expected exception was not thrown');
        } catch (Phergie_Connection_Exception $e) {
            return;
        } catch (Exception $e) {
            $this->fail('Unexpected exception was thrown');
        }
    }

    /**
     * Tests that attempting to set an invalid value for the encoding
     * results in an exception.
     *
     * @return void
     */
    public function testSetEncodingWithInvalidValue()
    {
        $connection = new Phergie_Connection;
        try {
            $connection->setEncoding('blah');
            $this->fail('Expected exception was not thrown');
        } catch (Phergie_Connection_Exception $e) {
            return;
        } catch (Exception $e) {
            $this->fail('Unexpected exception was thrown');
        }

        if (extension_loaded('mbstring')) {
            return;
        }

        try {
            $connection->setEncoding('UTF-8');
        } catch (Phergie_Connection_Exception $e) {
            return;
        } catch (Exception $e) {
            $this->fail('Unexpected exception was thrown');
        }
    }

    /**
     * Tests that options can be set collectively after the connection is
     * instantiated.
     *
     * @return void
     */
    public function testSetOptions()
    {
        $connection = new Phergie_Connection;
        $connection->setOptions($this->options);
        foreach ($this->options as $key => $value) {
            $this->assertEquals($value, $connection->{'get' . ucfirst($key)}());
        }
    }
}
