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
 * Unit test suite for Phergie_Config.
 *
 * @category Phergie
 * @package  Phergie_Tests
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Tests
 */
class Phergie_ConfigTest extends PHPUnit_Framework_TestCase
{
    /**
     * Configuration object being tested
     *
     * @var Phergie_Config
     */
    private $config;

    /**
     * Path to temporary files used to test the configuration object
     *
     * @var string
     */
    private $files;

    /**
     * Initializes the configuration instance and temporary testing file.
     *
     * @return void
     */
    public function setUp()
    {
        $this->config = new Phergie_Config;
        $this->files = array();
    }

    /**
     * Creates a temporary testing file that will be destroyed after the
     * current test method terminates if the file still exists.
     *
     * @return string Path to the file
     */
    private function createTempFile()
    {
        $path = tempnam(sys_get_temp_dir(), 'Phergie');
        $this->files[] = $path;
        return $path;
    }

    /**
     * Deletes the temporary testing file.
     *
     * @return void
     */
    public function tearDown()
    {
        foreach ($this->files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    /**
     * Tests that the configuration object can be accessed using array
     * syntax.
     *
     * @return void
     */
    public function testImplementsArrayAccess()
    {
        $reflector = new ReflectionObject($this->config);
        $this->assertTrue(
            $reflector->implementsInterface('ArrayAccess'),
            'Config instance does not implement ArrayAccess'
        );
    }

    /**
     * Tests that the configuration object properly indicates that an
     * undefined setting is undefined.
     *
     * @return void
     */
    public function testImplementsOffsetExists()
    {
        $value = isset($this->config['foo']);
        $this->assertFalse($value);
    }

    /**
     * Tests that the configuration object returns null for an undefined
     * setting.
     *
     * @return void
     */
    public function testImplementsOffsetGet()
    {
        $value = $this->config['foo'];
        $this->assertNull($value);
    }

    /**
     * Tests that the configuration object allows setting values to be
     * changed.
     *
     * @return void
     * @depends testImplementsOffsetExists
     * @depends testImplementsOffsetGet
     */
    public function testImplementsOffsetSet()
    {
        $this->config['foo'] = 'bar';
        $this->assertTrue(isset($this->config['foo']));
        $this->assertEquals('bar', $this->config['foo']);
    }

    /**
     * Tests that the configuration object allows setting values to be
     * removed.
     *
     * @return void
     * @depends testImplementsOffsetGet
     * @depends testImplementsOffsetSet
     */
    public function testImplementsUnset()
    {
        $file = $this->createTempFile();
        file_put_contents($file, '<?php return array("foo" => "bar");');
        $this->config->read($file);
        unset($this->config['foo']);
        $this->assertNull($this->config['foo']);
    }

    /**
     * Tests that an exception is thrown when the configuration object is
     * instructed to read a file when the current bot process does not have
     * sufficient permissions to do so.
     *
     * @return void
     */
    public function testReadThrowsExceptionForUnreadableFile()
    {
        $file = $this->createTempFile();
        if (!chmod($file, 0000)) {
            $this->markTestSkipped('chmod() call to make file unreadable failed');
        }
        try {
            $this->config->read($file);
            $this->fail('Expected exception for unreadable file was not thrown');
        } catch (Phergie_Config_Exception $e) {
            if ($e->getCode() != Phergie_Config_Exception::ERR_FILE_NOT_READABLE) {
                $this->fail('Unexpected ' . get_class($e) . ' thrown with code ' . $e->getCode());
            }
        }
    }

    /**
     * Tests that an exception is thrown when the configuration object is
     * instructed to read a file that does not exist.
     *
     * @return void
     */
    public function testReadThrowsExceptionForNonexistentFile()
    {
        $file = $this->createTempFile();
        unlink($file);
        try {
            $this->config->read($file);
            $this->fail('Expected exception for nonexistent file was not thrown');
        } catch (Phergie_Config_Exception $e) {
            if ($e->getCode() != Phergie_Config_Exception::ERR_FILE_NOT_FOUND) {
                $this->fail('Unexpected ' . get_class($e) . ' thrown with code ' . $e->getCode());
            }
        }
    }

    /**
     * Tests that an exception is thrown when the configuration object is
     * instructed to read a file that does not return an array.
     *
     * @return void
     */
    public function testReadThrowsExceptionForNoReturnedArray()
    {
        $file = $this->createTempFile();
        try {
            $this->config->read($file);
        } catch (Phergie_Config_Exception $e) {
            if ($e->getCode() != Phergie_Config_Exception::ERR_ARRAY_NOT_RETURNED) {
                $this->fail('Unexpected ' . get_class($e) . ' thrown with code ' . $e->getCode());
            }
        }
    }

    /**
     * Tests that the configuration object is able to read a valid
     * configuration file successfully and implements a fluent interface.
     *
     * @return void
     * @depends testImplementsOffsetExists
     * @depends testImplementsOffsetGet
     */
    public function testReadWithValidFile()
    {
        $file = $this->createTempFile();
        file_put_contents($file, '<?php return array("foo" => "bar");');

        $returned = $this->config->read($file);
        $this->assertSame(
            $this->config,
            $returned,
            'read() does not implement a fluent interface'
        );

        $this->assertEquals('bar', $this->config['foo']);
    }

    /**
     * Tests that the configuration object is able to read an associative
     * array.
     *
     * @return void
     * @depends testImplementsOffsetExists
     * @depends testImplementsOffsetGet
     */
    public function testReadArray()
    {
        $returned = $this->config->readArray(array('foo' => 'bar'));
        $this->assertSame(
            $this->config,
            $returned,
            'readArray() does not implement a fluent interface'
        );
        $this->assertEquals($this->config['foo'], 'bar');
    }

    /**
     * Tests that the configuration object is able to write setting values
     * back out to multiple files.
     *
     * @return void
     * @depends testImplementsOffsetExists
     * @depends testImplementsOffsetGet
     * @depends testImplementsOffsetSet
     */
    public function testWrite()
    {
        $file1 = $this->createTempFile();
        $file2 = $this->createTempFile();

        file_put_contents($file1, '<?php return array("foo" => "bar");');
        file_put_contents($file2, '<?php return array("baz" => "bay");');

        $this->config->read($file1);
        $this->config->read($file2);

        $this->config['foo'] = 'test1';
        $this->config['baz'] = 'test2';

        $returned = $this->config->write();
        $this->assertSame(
            $returned,
            $this->config,
            'write() does not implement a fluent interface'
        );

        $this->config = new Phergie_Config;
        $this->config->read($file1);
        $this->config->read($file2);

        $this->assertEquals('test1', $this->config['foo']);
        $this->assertEquals('test2', $this->config['baz']);
    }
}
