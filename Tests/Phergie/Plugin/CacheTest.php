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
 * Unit test suite for Phergie_Plugin_Cache.
 *
 * @category Phergie
 * @package  Phergie_Tests
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Tests
 */
class Phergie_Plugin_CacheTest extends Phergie_Plugin_TestCase
{
    public function testStoreAndFetch()
    {
        $this->assertEquals(false, $this->plugin->fetch('bar'));

        $this->plugin->store('bar', 'foo');
        $this->assertEquals('foo', $this->plugin->fetch('bar'));

        $this->plugin->store('bar', 'bar');
        $this->assertEquals('bar', $this->plugin->fetch('bar'));
    }

    public function testStoreTtl()
    {
        $this->assertEquals(false, $this->plugin->fetch('bar'));

        $this->plugin->store('bar', 'foo', 1);
        $this->assertEquals('foo', $this->plugin->fetch('bar'));

        $this->plugin->store('bar', 'bar', -1); // Small hack
        $this->assertEquals(false, $this->plugin->fetch('bar'));
    }

    public function testStoreDoesntOverwrite()
    {
        $this->assertEquals(false, $this->plugin->fetch('bar'));

        $this->plugin->store('bar', 'foo');
        $this->assertEquals('foo', $this->plugin->fetch('bar'));

        $this->plugin->store('bar', 'bar', null, false);
        $this->assertEquals('foo', $this->plugin->fetch('bar'));
    }

    public function testRegressionStoreShouldOverwriteWhenValueExpired()
    {
        $this->plugin->store('bar', 'bar');
        $this->assertEquals('bar', $this->plugin->fetch('bar'));

        $this->plugin->store('bar', 'bar', -1); // Small hack
        // Intentional no assert (fetch removes outdated key)
        $this->plugin->store('bar', 'foo', null, false);
        $this->assertEquals('foo', $this->plugin->fetch('bar'));
    }

    public function testExpire()
    {
        $this->plugin->store('bar', 'bar');
        $this->assertEquals('bar', $this->plugin->fetch('bar'));

        $this->plugin->expire('bar');
        $this->assertEquals(false, $this->plugin->fetch('bar'));

        $this->plugin->store('bar', 'foo');
        $this->assertEquals('foo', $this->plugin->fetch('bar'));
    }

    public function testExpireNonexistentKey()
    {
        $this->assertFalse($this->plugin->fetch('bar'));
        $this->assertFalse($this->plugin->expire('bar'));
    }
}
