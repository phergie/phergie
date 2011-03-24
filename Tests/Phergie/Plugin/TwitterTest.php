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
 * Unit test suite for plugin classes.
 *
 * @category Phergie
 * @package  Phergie_Tests
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Tests
 */
abstract class Phergie_Plugin_TwitterTest extends Phergie_Plugin_TestCase
{
    /**
     * Tests for appropriate plugin requirements.
     *
     * @return void
     */
    public function testPluginRequirements()
    {
        $this->assertRequiresPlugin('Url');
        $this->assertRequiresPlugin('Encoding');
        $this->assertRequiresPlugin('Time');
        $this->plugin->onLoad();
    }

    /**
     * Verifies that the default Twitter client instance is of the proper
     * class.
     *
     * @return void
     */
    public function testTwitterClass()
    {
        $this->assertInstanceOf('Twitter', $this->plugin->getTwitter());
    }
}
