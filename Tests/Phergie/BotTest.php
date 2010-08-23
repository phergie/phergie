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
 * Unit test suite for Phergie_Bot.
 *
 * @category Phergie
 * @package  Phergie_Tests
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Tests
 */
class Phergie_BotTest extends Phergie_TestCase
{
    /**
     * Instance of the class to test
     *
     * @var Phergie_Bot
     */
    private $bot;

    /**
     * Mock driver
     *
     * @var Phergie_Driver_Abstract
     */
    private $driver;

    /**
     * Instantiates the class to test.
     *
     * @return void
     */
    public function setUp()
    {
        $this->bot = new Phergie_Bot;
    }

    /**
     * Returns a mock driver instance.
     *
     * @return Phergie_Driver_Abstract
     */
    private function getMockDriver()
    {
        if (empty($this->driver)) {
            if (!class_exists('Phergie_Driver_Mock', false)) {
                $this->driver = $this->getMock(
                    'Phergie_Driver_Abstract',
                    array(),
                    array(),
                    'Phergie_Driver_Mock'
                );
            } else {
                $this->driver = new Phergie_Driver_Mock;
            }
        }
        return $this->driver;
    }

    /**
     * Tests that a default configuration object is used if none is set.
     *
     * @return void
     */
    public function testGetConfig()
    {
        file_put_contents('Settings.php', '<?php return array();');
        $config = $this->bot->getConfig();
        $this->assertType('Phergie_Config', $config);
        unlink('Settings.php');
    }

    /**
     * Tests that a configuration setting value is returned when one is set.
     *
     * @return void
     */
    public function testGetConfigUsesAvailableValue()
    {
        $this->bot->setConfig($this->getMockConfig());
        $this->setConfig('foo', 'bar');
        $this->assertEquals('bar', $this->bot->getConfig('foo', 'baz'));
    }

    /**
     * Tests that a default configuration setting value is returned when
     * specified and no value is available for that setting.
     *
     * @return void
     */
    public function testGetConfigUsesDefaultValue()
    {
        $this->bot->setConfig($this->getMockConfig());
        $this->assertEquals('bar', $this->bot->getConfig('foo', 'bar'));
    }

    /**
     * Tests that a custom configuration object can be used.
     *
     * @return void
     */
    public function testSetConfig()
    {
        $config = $this->getMockConfig();
        $this->bot->setConfig($config);
        $this->assertSame($config, $this->bot->getConfig());
    }

    /**
     * Tests that a default driver is used when none is explicitly set by
     * injection or configuration.
     *
     * @return void
     * @depends testSetConfig
     */
    public function testGetDriverReturnsDefault()
    {
        $this->bot->setConfig($this->getMockConfig());
        $this->assertType('Phergie_Driver_Streams', $this->bot->getDriver());
    }

    /**
     * Tests that a driver is used when one is specified via configuration.
     *
     * @return void
     * @depends testSetConfig
     */
    public function testGetDriverUsesConfig()
    {
        $this->getMockDriver();
        $this->bot->setConfig($this->getMockConfig());
        $this->setConfig('driver', 'Mock');
        $this->assertType('Phergie_Driver_Mock', $this->bot->getDriver());
    }

    /**
     * Tests that a custom driver can be used.
     *
     * @return void
     */
    public function testSetDriver()
    {
        $driver = $this->getMockDriver();
        $this->bot->setDriver($driver);
        $this->assertSame($driver, $this->bot->getDriver());
    }

    /**
     * Tests that a default plugin handler is used when none is set.
     *
     * @return void
     */
    public function testGetPluginHandler()
    {
        $config = $this->getMockConfig();
        $this->bot->setConfig($config);
        $plugins = $this->bot->getPluginHandler();
        $this->assertType('Phergie_Plugin_Handler', $plugins);
    }

    /**
     * Tests that a custom plugin handler can be set.
     *
     * @return void
     */
    public function testSetPluginHandler()
    {
        $plugins = $this->getMockPluginHandler();
        $this->bot->setPluginHandler($plugins);
        $this->assertSame($plugins, $this->bot->getPluginHandler());
    }

    /**
     * Tests that a default event handler is used when none is set.
     *
     * @return void
     */
    public function testGetEventHandler()
    {
        $events = $this->bot->getEventHandler();
        $this->assertType('Phergie_Event_Handler', $events);
    }

    /**
     * Tests that a custom event handler can be set.
     *
     * @return void
     */
    public function testSetEventHandler()
    {
        $events = $this->getMockEventHandler();
        $this->bot->setEventHandler($events);
        $this->assertSame($events, $this->bot->getEventHandler());
    }

    /**
     * Tests that a default connection handler is used when none is set.
     *
     * @return void
     */
    public function testGetConnectionHandler()
    {
        $connections = $this->bot->getConnectionHandler();
        $this->assertType('Phergie_Connection_Handler', $connections);
    }

    /**
     * Tests that a custom connection handler can be set.
     *
     * @return void
     */
    public function testSetConnectionHandler()
    {
        $connections = $this->getMockConnectionHandler();
        $this->bot->setConnectionHandler($connections);
        $this->assertSame($connections, $this->bot->getConnectionHandler());
    }

    /**
     * Tests that a default end-user interface is used when none is set.
     *
     * @return void
     */
    public function testGetUi()
    {
        $this->bot->setConfig($this->getMockConfig());
        $ui = $this->bot->getUi();
        $this->assertType('Phergie_Ui_Console', $ui);
    }

    /**
     * Tests that a custom end-user interface can be set.
     *
     * @return void
     */
    public function testSetUi()
    {
        $ui = $this->getMockUi();
        $this->bot->setUi($ui);
        $this->assertSame($ui, $this->bot->getUi());
    }

    /**
     * Tests that a default event processor is used when none is set by
     * injection or configuration.
     *
     * @return void
     */
    public function testGetProcessorUsesDefault()
    {
        $config = $this->getMockConfig();
        $this->bot->setConfig($config);
        $processor = $this->bot->getProcessor();
        $this->assertType('Phergie_Process_Standard', $processor);
    }

    /**
     * Tests that a custom event processor can be set via configuration.
     *
     * @return void
     */
    public function testGetProcessorUsesConfig()
    {
        $config = $this->getMockConfig();
        $this->bot->setConfig($config);
        $this->setConfig('processor', 'Mock');
        $this->getMockForAbstractClass(
            'Phergie_Process_Abstract',
            array($this->bot),
            'Phergie_Process_Mock'
        );
        $this->assertType('Phergie_Process_Mock', $this->bot->getProcessor());
    }

    /**
     * Tests that a custom event procesor can be set via configuration.
     *
     * @return void
     */
    public function testSetProcessor()
    {
        $config = $this->getMockConfig();
        $this->bot->setConfig($config);
        $processor = $this->getMockForAbstractClass(
            'Phergie_Process_Abstract',
            array($this->bot)
        );
        $this->bot->setProcessor($processor);
        $this->assertSame($processor, $this->bot->getProcessor());
    }

    /**
     * Injects all dependencies into the bot instance being tested.
     *
     * @return void
     */
    private function injectDependencies()
    {
        $this->bot->setConfig($this->getMockConfig());
        $this->bot->setDriver($this->getMockDriver());
        $this->bot->setConnectionHandler($this->getMockConnectionHandler());
        $this->bot->setUi($this->getMockUi());
        $this->bot->setPluginHandler($this->getMockPluginHandler());
        $this->bot->setProcessor($this->getMockProcessor($this->bot));
        $this->bot->setEventHandler($this->getMockEventHandler());
    }

    /**
     * Tests that a default timezone for date functions is used if none is
     * set.
     *
     * @return void
     */
    public function testRunUsesDefaultTimezone()
    {
        $this->injectDependencies();
        $this->bot->run();
        $this->assertEquals('UTC', date_default_timezone_get());
    }

    /**
     * Tests that a custom timezone is used when set via configuration.
     *
     * @return void
     */
    public function testRunUsesTimeZonesFromConfig()
    {
        $timezone = 'America/Chicago';
        $this->injectDependencies();
        $this->setConfig('timezone', $timezone);
        $this->bot->run();
        $this->assertEquals($timezone, date_default_timezone_get());
    }

    /**
     * Tests that plugins are read from the configuration and added to the
     * plugin handler when they have no dependencies.
     *
     * @return void
     */
    public function testRunLoadsPluginWithoutDependencies()
    {
        $plugin = 'MockWithoutDependencies';
        $this->injectDependencies();
        $this->setConfig('plugins', array($plugin));

        $plugins = $this->getMockPluginHandler();
        $plugins
            ->expects($this->once())
            ->method('setAutoload')
            ->with(false);
        $plugins
            ->expects($this->once())
            ->method('addPlugin')
            ->with($plugin);

        $ui = $this->getMockUi();
        $ui
            ->expects($this->once())
            ->method('onPluginLoad')
            ->with($plugin);

        $this->bot->run();
    }

    /**
     * Tests that the exception is handled when a plugin is loaded with a
     * dependency that is unavailable.
     *
     * @return void
     */
    public function testRunLoadsPluginWithUnavailableDependencies()
    {
        $plugin = 'MockWithDependencies';
        $this->injectDependencies();
        $this->setConfig('plugins', array($plugin));
        $this->setConfig('plugins.autoload', true);

        $message = 'Dependency unavailable';
        $exception = new Phergie_Plugin_Exception($message);
        $plugins = $this->getMockPluginHandler();
        $plugins
            ->expects($this->once())
            ->method('setAutoload')
            ->with(true);
        $plugins
            ->expects($this->once())
            ->method('addPlugin')
            ->with($plugin)
            ->will($this->throwException($exception));

        $ui = $this->getMockUi();
        $ui
            ->expects($this->once())
            ->method('onPluginFailure')
            ->with($plugin, $exception->getMessage());

        $this->bot->run();
    }

    /**
     * Tests that connections are read from the configuration and added to
     * the connection handler.
     *
     * @return void
     */
    public function testRunLoadsConnections()
    {
        $data = array(
            'host' => 'host',
            'nick' => 'nick',
            'username' => 'username',
            'realname' => 'realname'
        );

        $this->injectDependencies();
        $this->setConfig('connections', array($data));

        $connections = $this->getMockConnectionHandler();
        $connections
            ->expects($this->once())
            ->method('addConnection')
            ->with($this->isInstanceOf('Phergie_Connection'));

        $ui = $this->getMockUi();
        $ui
            ->expects($this->once())
            ->method('onConnect')
            ->with($data['host']);

        $driver = $this->getMockDriver();
        $driver
            ->expects($this->once())
            ->method('setConnection')
            ->with($this->isInstanceOf('Phergie_Connection'))
            ->will($this->returnValue($this->driver));
        $driver
            ->expects($this->once())
            ->method('doConnect');

        $plugins = $this->getMockPluginHandler();
        $plugins
            ->expects($this->at(0))
            ->method('__call')
            ->with($this->equalTo('setConnection'), $this->isType('array'));
        $plugins
            ->expects($this->at(1))
            ->method('__call')
            ->with('onConnect');

        $this->bot->run();
    }

    /**
     * Tests that the bot handles events when a connection is present.
     *
     * @return void
     */
    public function testRunHandlesEvents()
    {
        $this->injectDependencies();

        $connections = $this->getMockConnectionHandler();
        $connections
            ->expects($this->exactly(2))
            ->method('count')
            ->will($this->onConsecutiveCalls(1, 0));

        $ui = $this->getMockUi();
        $ui
            ->expects($this->once())
            ->method('onShutdown');

        $processor = $this->getMockProcessor($this->bot);
        $processor
            ->expects($this->once())
            ->method('handleEvents');

        $this->bot->run();
    }
}
