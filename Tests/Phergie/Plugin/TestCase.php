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
abstract class Phergie_Plugin_TestCase extends PHPUnit_Framework_TestCase
{
    /**
     * Mock configuration
     *
     * @var Phergie_Config
     */
    protected $config;

    /**
     * Associative array for configuration setting values, accessed by the
     * mock configuration object using a callback
     *
     * @var array
     */
    protected $settings = array();

    /**
     * Mock connection
     *
     * @var Phergie_Connection
     */
    protected $connection;

    /**
     * Mock event handler
     *
     * @var Phergie_Event_Handler
     */
    protected $events;

    /**
     * Mock plugin handler
     *
     * @var Phergie_Plugin_Handler
     */
    protected $plugins;

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
     * User nick used in any events requiring one
     *
     * @var string
     */
    protected $nick = 'nick';

    /**
     * Event source used in any events requiring one
     *
     * @var string
     */
    protected $source = '#channel';

    /**
     * Initializes instance properties.
     *
     * @return void
     */
    public function setUp()
    {
        if (empty($this->pluginClass)) {
            $this->pluginClass = preg_replace('/Test$/', '', get_class($this));
        }

        if (empty($this->plugin)) {
            $this->plugin = new $this->pluginClass;
        }

        $this->plugin->setConfig($this->getMockConfig());
        $this->plugin->setConnection($this->getMockConnection());
        $this->plugin->setEventHandler($this->getMockEventHandler());
        $this->plugin->setPluginHandler($this->getMockPluginHandler());
    }

    /**
     * Destroys all initialized instance properties.
     *
     * @return void
     */
    public function tearDown()
    {
        unset(
            $this->plugins,
            $this->events,
            $this->connection,
            $this->config,
            $this->plugin
        );
    }

    /**
     * Returns a mock configuration object.
     *
     * @return Phergie_Config
     */
    protected function getMockConfig()
    {
        if (empty($this->config)) {
            $this->config = $this->getMock('Phergie_Config', array('offsetExists', 'offsetGet'));
            $this->config
                ->expects($this->any())
                ->method('offsetExists')
                ->will($this->returnCallback(array($this, 'configOffsetExists')));
            $this->config
                ->expects($this->any())
                ->method('offsetGet')
                ->will($this->returnCallback(array($this, 'configOffsetGet')));
        }
        return $this->config;
    }

    /**
     * Returns whether a specific configuration setting has a value. Only
     * intended for use by this class, but must be public for PHPUnit to
     * call them.
     *
     * @param string $name Name of the setting
     *
     * @return boolean TRUE if the setting has a value, FALSE otherwise
     */
    public function configOffsetExists($name)
    {
        return isset($this->settings[$name]);
    }

    /**
     * Returns the value of a specific configuration setting. Only intended
     * for use by this class, but must be public for PHPUnit to call them.
     *
     * @param string $name Name of the setting
     *
     * @return mixed Value of the setting
     */
    public function configOffsetGet($name)
    {
        return $this->settings[$name];
    }

    /**
     * Returns a mock connection object.
     *
     * @return Phergie_Connection
     */
    protected function getMockConnection()
    {
        if (empty($this->connection)) {
            $this->connection = $this->getMock('Phergie_Connection');
            $this->connection
                ->expects($this->any())
                ->method('getNick')
                ->will($this->returnValue($this->nick));
        }
        return $this->connection;
    }

    /**
     * Returns a mock event handler object.
     *
     * @return Phergie_Event_Handler
     */
    protected function getMockEventHandler()
    {
        if (empty($this->events)) {
            $this->events = $this->getMock('Phergie_Event_Handler', array('addEvent'));
        }
        return $this->events;
    }

    /**
     * Returns a mock plugin handler object.
     *
     * @return Phergie_Plugin_Handler
     */
    protected function getMockPluginHandler()
    {
        if (empty($this->plugins)) {
            $config = $this->getMockConfig();
            $events = $this->getMockEventHandler();
            $this->plugins = $this->getMock(
                'Phergie_Plugin_Handler',
                array(), // mock everything
                array($config, $events)
            );
        }
        return $this->plugins;
    }

    /**
     * Returns a mock event object.
     *
     * @param string $type   Event type
     * @param array  $args   Optional associative array of event arguments
     * @param string $nick   Optional user nick to associate with the event
     * @param string $source Optional user nick or channel name to associate
     *        with the event as its source
     *
     * @return Phergie_Event_Request
     */
    protected function getMockEvent($type, array $args = array(),
        $nick = null, $source = null
    ) {
        $methods = array('getNick', 'getSource');
        foreach (array_keys($args) as $arg) {
            if (is_int($arg) || ctype_digit($arg)) {
                $methods[] = 'getArgument';
            } else {
                $methods[] = 'get' . ucfirst($arg);
            }
        }

        $event = $this->getMock(
            'Phergie_Event_Request',
            $methods
        );

        $nick = $nick ? $nick : $this->nick;
        $event
            ->expects($this->any())
            ->method('getNick')
            ->will($this->returnValue($nick));

        $source = $source ? $source : $this->source;
        $event
            ->expects($this->any())
            ->method('getSource')
            ->will($this->returnValue($source));

        foreach ($args as $key => $value) {
            if (is_int($key) || ctype_digit($key)) {
                $event
                    ->expects($this->any())
                    ->method('getArgument')
                    ->with($key)
                    ->will($this->returnValue($value));
            } else {
                $event
                    ->expects($this->any())
                    ->method('get' . ucfirst($key))
                    ->will($this->returnValue($value));
            }
        }

        return $event;
    }

    /**
     * Sets the value of a configuration setting.
     *
     * @param string $setting Name of the setting
     * @param mixed  $value   Value for the setting
     *
     * @return void
     */
    protected function setConfig($setting, $value)
    {
        $this->settings[$setting] = $value;
    }

    /**
     * Returns the absolute path to the Phergie/Plugin directory. Useful in
     * conjunction with getMockDatabase().
     *
     * @param string $subpath Optional path to append to the directory path
     *
     * @return string Directory path
     */
    protected function getPluginsPath($subpath = null)
    {
        $path = realpath(dirname(__FILE__) . '/../../../Phergie/Plugin');
        if (!empty($subpath)) {
            $path .= '/' . ltrim($subpath, '/');
        }
        return $path;
    }

    /**
     * Modifies the event handler to include an expectation of an event
     * being added by the plugin being tested. Note that this must be called
     * BEFORE executing the plugin code intended to initiate the event.
     *
     * @param string $type Event type
     * @param array  $args Optional enumerated array of event arguments
     *
     * @return void
     */
    protected function assertEmitsEvent($type, array $args = array())
    {
        $this->events
            ->expects($this->at(0))
            ->method('addEvent')
            ->with($this->plugin, $type, $args);
    }

    /**
     * Modifies the event handler to include an expectation of an event NOT
     * being added by the plugin being tested. Note that this must be called
     * BEFORE executing plugin code that may initiate the event.
     *
     * @param string $type Event type
     * @param array  $args Optional enumerated array of event arguments
     *
     * @return void
     */
    protected function assertDoesNotEmitEvent($type, array $args = array())
    {
        // Ugly hack to get around an issue in PHPUnit
        // @link http://github.com/sebastianbergmann/phpunit-mock-objects/issues/issue/5#issue/5/comment/343524
        $callback = create_function(
            '$plugin, $type, $args',
            'if (get_class($plugin) == "' . $this->pluginClass . '"
            && $type == "' . $type . '"
            && $args == "' . var_export($args, true) . '") {
                trigger_error("Instance of ' . $this->pluginClass
                . ' unexpectedly emitted event of type ' . $type
                . '", E_USER_ERROR);
            }'
        );

        $this->events
            ->expects($this->any())
            ->method('addEvent')
            ->will($this->returnCallback($callback));
    }

    /**
     * Modifies the plugin handler to include an expectation of a plugin
     * being retrieved, indicating a dependency. Note that this must be
     * called BEFORE executing the plugin code that may load that plugin
     * dependency, which is usually located in onLoad().
     *
     * @param string $name Short name of the plugin required as a dependency
     *
     * @return void
     */
    public function assertRequiresPlugin($name)
    {
        $this->plugins
            ->expects($this->atLeastOnce())
            ->method('getPlugin')
            ->with($name);
    }

    /**
     * Creates an in-memory copy of a specified SQLite database file and
     * returns a connection to it.
     *
     * @param string $path Path to the SQLite file to copy
     *
     * @return PDO Connection to the database copy
     */
    public function getMockDatabase($path)
    {
        $original = new PDO('sqlite:' . $path);
        $copy = new PDO('sqlite::memory:');

        $result = $original->query('SELECT sql FROM sqlite_master');
        while ($sql = $result->fetchColumn()) {
            $copy->exec($sql);
        }

        $tables = array();
        $result = $original->query('SELECT name FROM sqlite_master WHERE type = "table"');
        while ($table = $result->fetchColumn()) {
            $tables[] = $table;
        }

        foreach ($tables as $table) {
            $result = $original->query('SELECT * FROM ' . $table);
            $insert = null;
            $copy->beginTransaction();
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $columns = array_keys($row);
                if (empty($insert)) {
                    $insert = $copy->prepare(
                        'INSERT INTO "' . $table . '" (' .
                        '"' . implode('", "', $columns) . '"' .
                        ') VALUES (' .
                        ':' . implode(', :', $columns) .
                        ')'
                    );
                }
                $insert->execute($row);
            }
            $copy->commit();
            unset($insert);
        }

        return $copy;
    }
}
