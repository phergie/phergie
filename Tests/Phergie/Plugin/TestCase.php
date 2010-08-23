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
        parent::tearDown();

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
     * Modifies the plugin handler to include an expectation of a plugin
     * being removed. Note that this must be called BEFORE executing the
     * plugin code that may remove that plugin.
     *
     * @param string|Phergie_Plugin_Abstract $plugin Short name of the plugin
     *        or the plugin instance to be removed
     *
     * @return void
     */
    public function assertRemovesPlugin($plugin)
    {
        $this->plugins
            ->expects($this->once())
            ->method('removePlugin')
            ->with($plugin);
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
