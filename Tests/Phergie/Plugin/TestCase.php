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
 * Unit test suite for plugin classes.
 *
 * @category Phergie
 * @package  Phergie_Tests
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Tests
 */
abstract class Phergie_Plugin_TestCase extends Phergie_TestCase
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
     * Plugins that should be required in this test
     *
     * @var array
     */
    private $expectedRequiredPlugins;

    /**
     * Plugins that are actually required in this test
     *
     * @var array
     */
    private $actualRequiredPlugins;

    /**
     * Plugins that should be removed in this test
     *
     * @var array
     */
    private $expectedRemovedPlugins;

    /**
     * Plugins that are actually removed in this test
     *
     * @var array
     */
    private $actualRemovedPlugins;

    /**
     * Mocks of plugin dependencies
     *
     * @var array
     */
    private $mockPlugins;

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

        $plugins = $this->getMockPluginHandler();
        $plugins->expects($this->any())
            ->method('getPlugin')
            ->will($this->returnCallback(array($this, 'requirePlugin')));
        $plugins->expects($this->any())
            ->method('removePlugin')
            ->will($this->returnCallback(array($this, 'removePlugin')));
        $this->plugin->setPluginHandler($plugins);

        $this->mockPlugins = array();

        $this->expectedRequiredPlugins = array();
        $this->actualRequiredPlugins = array();

        $this->expectedRemovedPlugins = array();
        $this->actualRemovedPlugins = array();
    }

    /**
     * Destroys all initialized instance properties.
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();

        // Check required plugins
        if ($this->expectedRequiredPlugins) {
            $diff = array_diff($this->expectedRequiredPlugins, $this->actualRequiredPlugins);
            $this->assertSame(
                0,
                count($diff),
                'Expected and actual required plugins differ: ' . implode(', ', $diff)
            );
        }

        // Check removed plugins
        if ($this->expectedRemovedPlugins) {
            $diff = array_diff($this->expectedRequiredPlugins, $this->actualRequiredPlugins);
            $this->assertSame(
                0,
                count($diff),
                'Expected and actual removed plugins differ: ' . implode(', ', $diff)
            );
        }

        unset($this->plugin);
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
        $path = dirname(dirname(dirname(dirname(__FILE__)))) . '/Phergie/Plugin';
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
     * Records an actual plugin requirement.
     *
     * @param string $name Short name of the plugin required as a dependency
     *
     * @return Phergie_Plugin_Abstract Mock instance of the specified plugin
     */
    public function requirePlugin($name)
    {
        if (!isset($this->mockPlugins[$name])) {
            $this->actualRequiredPlugins[] = $name;
            $this->mockPlugins[$name] = $this->getMock('Phergie_Plugin_' . ucfirst($name));
        }
        return $this->mockPlugins[$name];
    }

    /**
     * Modifies the plugin handler to include an expectation of a plugin
     * being retrieved, indicating a dependency. Note that this must be
     * called BEFORE executing the plugin code that may load that plugin
     * dependency, which is usually located in onLoad().
     *
     * @param string|array $name Short name of the plugin required as a
     *        dependency or an array containing multiple instances thereof
     *
     * @return void
     */
    public function assertRequiresPlugin($name)
    {
        if (is_array($name)) {
            $this->expectedRequiredPlugins = array_merge($this->expectedRequiredPlugins, $name);
        } else {
            $this->expectedRequiredPlugins[] = $name;
        }
    }

    /**
     * Records an actual plugin removal.
     *
     * @param string|Phergie_Plugin_Abstract $plugin Short name of the plugin
     *        or the plugin instance to be removed
     *
     * @return void
     */
    public function removePlugin($plugin)
    {
        $this->actualRemovedPlugins[] = $plugin;
    }
    /**
     * Modifies the plugin handler to include an expectation of a plugin
     * being removed. Note that this must be called BEFORE executing the
     * plugin code that may remove that plugin.
     *
     * @param string|Phergie_Plugin_Abstract|array $plugin Short name of the
     *        plugin or the plugin instance to be removed or an array of
     *        multiple instances of either
     *
     * @return void
     */
    public function assertRemovesPlugin($plugin)
    {
        if (is_array($plugin)) {
            $this->expectedRemovedPlugins = array_merge($this->expectedRemovedPlugins, $plugin);
        } else {
            $this->expectedRemovedPlugins[] = $plugin;
        }
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
        $result = $original->query(
            'SELECT name FROM sqlite_master WHERE type = "table"'
        );
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
