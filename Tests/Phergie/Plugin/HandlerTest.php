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
 * Unit test suite for Pherge_Plugin_Handler.
 *
 * @category Phergie
 * @package  Phergie_Tests
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Tests
 */
class Phergie_Plugin_HandlerTest extends PHPUnit_Framework_TestCase
{
    /**
     * Plugin handler instance being tested
     *
     * @var Phergie_Plugin_Handler
     */
    protected $handler;

    /**
     * Sets up a new handler instance before each test.
     *
     * @return void
     */
    public function setUp()
    {
        $this->handler = new Phergie_Plugin_Handler(
            new Phergie_Config(),
            new Phergie_Event_Handler()
        );
    }

    /**
     * Destroys the handler instance after each test
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->handler);
    }

    /**
     * Ensures that we can iterate over the handler
     *
     * @return void
     */
    public function testImplementsIterator()
    {
        $reflection = new ReflectionObject($this->handler);
        $this->assertTrue(
            $reflection->implementsInterface('IteratorAggregate')
        );

        $this->assertType(
            'Iterator', $this->handler->getIterator(),
            'getIterator() must actually return an Iterator'
        );
    }

    /**
     * Ensures a newly instantiated handler does not have plugins associated
     * with it
     *
     * @depends testImplementsIterator
     * @return void
     */
    public function testEmptyHandlerHasNoPlugins()
    {
        $count = 0;
        foreach ($this->handler as $plugin) {
            $count++;
        }

        $this->assertEquals(0, $count);
    }
    
    /**
     * Ensures a newly instantiated handler does not default to autoload
     *
     * @return void
     */
    public function testDefaultsToNotAutoload()
    {
        $this->assertFalse($this->handler->getAutoload());
    }

    /**
     * addPath provides a fluent interface
     *
     * @return void
     */
    public function testAddPathProvidesFluentInterface()
    {
        $handler = $this->handler->addPath(dirname(__FILE__));
        $this->assertSame($this->handler, $handler);
    }

    /**
     * addPath throws an exception when it cannot read the directory
     *
     * @return void
     */
    public function testAddPathThrowsExceptionOnUnreadableDirectory()
    {
        try {
            $this->handler->addPath('/an/unreadable/directory/path');
        } catch(Phergie_Plugin_Exception $e) {
            $this->assertEquals(
                Phergie_Plugin_Exception::ERR_DIRECTORY_NOT_READABLE,
                $e->getCode()
            );
            return;
        }

        $this->fail('An expected exception has not been raised.');
    }

    /**
     * adds a path into the plugin handler and then ensures that files
     * in that location can be found
     *
     * @return void
     */
    public function testAddPath()
    {
        $plugin_name = 'Mock';
        try {
            $this->handler->addPlugin($plugin_name);
        } catch(Phergie_Plugin_Exception $e) {
            $this->assertEquals(
                Phergie_Plugin_Exception::ERR_CLASS_NOT_FOUND,
                $e->getCode()
            );
            
            $this->handler->addPath(dirname(__FILE__), 'Phergie_Plugin_');

            try {
                $this->handler->addPlugin($plugin_name);
            } catch(Phergie_Plugin_Exception $e) {
                $this->fail(
                    'After adding the directory, the plugin was still '
                    . 'not found.'
                );
            }
            
            return;
        }

        $this->fail(
            'Before adding the directory, an expected exception'
            . 'was not raised'
        );
    }

    /**
     * Can add a plugin to the handler by shortname
     *
     * @return void
     */
    public function testAddPluginToHandlerByShortname()
    {
        $plugin_name = 'Mock';
        $this->handler->addPath(dirname(__FILE__), 'Phergie_Plugin_');

        $returned_plugin = $this->handler->addPlugin($plugin_name);
        $this->assertTrue($this->handler->hasPlugin($plugin_name));
        $this->assertType(
            'Phergie_Plugin_Mock',
            $this->handler->getPlugin($plugin_name)
        );
        $this->assertEquals(
            $this->handler->getPlugin($plugin_name),
            $returned_plugin
        );
    }


    /**
     * Can add a plugin to the handler by instance
     *
     * @return void
     */
    public function testAddPluginToHandlerByInstance()
    {
        $plugin = $this->getMock('Phergie_Plugin_Abstract');
        $plugin
            ->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('TestPlugin'));

        $returned_plugin = $this->handler->addPlugin($plugin);

        $this->assertTrue($this->handler->hasPlugin('TestPlugin'));
        $this->assertSame(
            $plugin, $returned_plugin,
            'addPlugin returns the same plugin'
        );
        $this->assertSame(
            $plugin, $this->handler->getPlugin('TestPlugin'),
            'getPlugin returns the same plugin'
        );
    }

    /**
     * addPlugin throws an exception when it can't find the plugin
     *
     * @return void
     */
    public function testAddPluginThrowsExceptionIfCannotFindPlugin()
    {
        try {
            $this->handler->addPlugin('TestPlugin');
        } catch(Phergie_Plugin_Exception $e) {
            $this->assertEquals(
                Phergie_Plugin_Exception::ERR_CLASS_NOT_FOUND,
                $e->getCode()
            );
            return;
        }

        $this->fail('An expected exception has not been raised.');
    }

    /**
     * addPlugin throws an exception when trying to instantiate a
     * class that doesn't extend from Phergie_Plugin_Abstract
     *
     * @return void
     */
    public function testAddPluginThrowsExceptionIfRequestingNonPlugin()
    {
        try {
            $this->handler->addPlugin('Handler');
        } catch(Phergie_Plugin_Exception $e) {
            $this->assertEquals(
                Phergie_Plugin_Exception::ERR_INCORRECT_BASE_CLASS,
                $e->getCode()
            );
            return;
        }

        $this->fail('An expected exception has not been raised.');
    }

    /**
     * addPlugin throws an exception when trying to instantiate a
     * class that can't be instantiated.
     *
     * @return void
     */
    public function testAddPluginThrowsExceptionIfPluginNotInstantiable()
    {
        $this->handler->addPath(dirname(__FILE__), 'Phergie_Plugin_');
        try {
            $this->handler->addPlugin('TestNonInstantiablePluginFromFile');
        } catch(Phergie_Plugin_Exception $e) {
            $this->assertEquals(
                Phergie_Plugin_Exception::ERR_CLASS_NOT_INSTANTIABLE,
                $e->getCode()
            );
            return;
        }

        $this->fail('An expected exception has not been raised.');
    }

    /**
     * addPlugin with shortname and arguments passes args to constructor
     *
     * @return null
     */
    public function testAddPluginShortnamePassesArgsToConstructor()
    {
        $plugin_name = 'Mock';
        $this->handler->addPath(dirname(__FILE__), 'Phergie_Plugin_');

        $arguments = array('a', 'b', 'c');

        $plugin = $this->handler->addPlugin($plugin_name, $arguments);
        $this->assertAttributeSame(
            $arguments,
            'args',
            $plugin,
            'Arguments passed in to addPlugin match the arguments '
            . 'the Mock plugin constructor received'
        );
    }

    /**
     * addPlugin passes Phergie_Config to instantiated plugin
     *
     * @return null
     */
    public function testAddPluginPassesPhergieConfigToInstantiatedPlugin()
    {
        $my_config = new Phergie_Config();
        $my_config['my_option'] = 'my_value';

        // create a new handler with this config
        unset($this->handler);
        $this->handler = new Phergie_Plugin_Handler(
            $my_config,
            new Phergie_Event_Handler()
        );

        $plugin_name = 'Mock';
        $this->handler->addPath(dirname(__FILE__), 'Phergie_Plugin_');

        $plugin = $this->handler->addPlugin($plugin_name);

        $this->assertSame(
            $my_config,
            $plugin->getConfig(),
            'addPlugin passes Phergie_Config to instantiated plugin'
        );
    }

    /**
     * addPlugin passes Phergie_Event_Handler to instantiated plugin
     *
     * @return null
     */
    public function testAddPluginPassesPhergieEventHandlerToInstantiatedPlugin()
    {
        $plugin = $this->getMock('Phergie_Plugin_Abstract');
        $plugin
            ->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('TestPlugin'));

        $my_event_handler = new Phergie_Event_Handler();
        $my_event_handler->addEvent($plugin, 'ping');

        // create a new plugin handler with this event handler
        unset($this->handler);
        $this->handler = new Phergie_Plugin_Handler(
            new Phergie_Config(),
            $my_event_handler
        );

        $plugin_name = 'Mock';
        $this->handler->addPath(dirname(__FILE__), 'Phergie_Plugin_');

        $plugin = $this->handler->addPlugin($plugin_name);

        $this->assertSame(
            $my_event_handler,
            $plugin->getEventHandler(),
            'addPlugin passes Phergie_Event_Handler to instantiated plugin'
        );
    }

    /**
     * @todo addPlugin calls onLoad() to instantiated plugin
     */

    /**
     * implements __isset
     *
     * @return void
     */
    public function testPluginHandlerImplementsIsset()
    {
        $plugin_name = 'TestPlugin';

        $this->assertFalse(isset($this->handler->{$plugin_name}));

        $plugin = $this->getMock('Phergie_Plugin_Abstract');
        $plugin
            ->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($plugin_name));

        $this->handler->addPlugin($plugin);

        $this->assertTrue(isset($this->handler->{$plugin_name}));

    }

    /**
     * addPlugin() returns the same plugin when requested twice
     *
     * @return void
     */
    public function testAddPluginReturnsSamePluginWhenAskedTwice()
    {
        $plugin_name = 'Mock';
        $this->handler->addPath(dirname(__FILE__), 'Phergie_Plugin_');

        $plugin1 = $this->handler->addPlugin($plugin_name);
        $plugin2 = $this->handler->addPlugin($plugin_name);
        $this->assertSame($plugin1, $plugin2);
    }

    
    /**
     * Tests an exception is thrown when trying to get a plugin
     * that is not already loaded and autoload is off
     *
     * @depends testDefaultsToNotAutoload
     * @return void
     */
    public function testExceptionThrownWhenLoadingPluginWithoutAutoload()
    {
        $this->handler->addPath(dirname(__FILE__), 'Phergie_Plugin_');

        try {
            $this->handler->getPlugin('Mock');
        } catch (Phergie_Plugin_Exception $expected) {
            $this->assertEquals(
                Phergie_Plugin_Exception::ERR_PLUGIN_NOT_LOADED,
                $expected->getCode()
            );
            return;
        }

        $this->fail('An expected exception has not been raised.');
    }
}
