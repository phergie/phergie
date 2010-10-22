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
     * Plugin instance being tested
     *
     * @var Phergie_Plugin_Abstract
     */
    protected $plugin;

    /**
     * Full name of the plugin class being tested, may be explicitly
     * specified in subclasses but is otherwise automatically derived from
     * the test case class name
     *
     * @var string
     */
    protected $pluginClass;

    /**
     * Initializes instance properties.
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();
    }

    /**
     * Destroys all initialized instance properties.
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();
        unset($this->plugin);
    }

    /**
     *	Tests the requirement of the Url Plugin
     *	
     *	@return void
     */
    public function testRequiresUrlPlguin()
    {
        $this->assertRequiresPlugin('Url');
        $this->assertRequiresPlugin('Encoding');
        $this->assertRequiresPlugin('Time');
        $this->plugin->onLoad();
    }

    /**
     *	Verifies the private twitter class is an instance of a twitter class
     *	@return void
     */
    public function testTwitterClass()
    {
        $this->assertType('Twitter', $this->plugin->getTwitter());
    }
}

