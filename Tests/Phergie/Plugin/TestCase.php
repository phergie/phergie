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
 * Unit test suite for Pherge_Plugin classes
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
     * @var Phergie_Event_Handler
     */
    protected $handler;

    /**
     * @var Phergie_Connection
     */
    protected $connection;

    /**
     * @var array
     */
    protected $eventArgs;

    /**
     * @var Phergie_Plugin_Abstract
     */
    protected $plugin;

    /**
     * @var array
     */
    protected $config = array();

    /**
     * Constructs a test case with the given name.
     *
     * @param  string $name
     * @param  array  $data
     * @param  string $dataName
     */
    public function __construct($name = NULL, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->connection = new Phergie_Connection();
        $this->handler    = new Phergie_Event_Handler();
    }

    /**
     * Assert that a given event type exists in the event handler
     * @param string $event
     * @param string $message
     */
    public function assertHasEvent($event, $message = null)
    {
        self::assertTrue($this->handler->hasEventOfType($event), $message);
    }

    /**
     * Assert that a given event type DOES NOT exist in the event handler
     * @param string $event
     * @param string $message
     */
    public function assertDoesNotHaveEvent($event, $message = null)
    {
        self::assertFalse($this->handler->hasEventOfType($event), $message);
    }

    /**
     * Assert that the emitter of the given command event was the given
     * plugin
     *
     * @param Phergie_Event_Command   $event
     * @param Phergie_Plugin_Abstract $plugin
     * @param string                  $message
     */
    public function assertEventEmitter(Phergie_Event_Command $event,
                                       Phergie_Plugin_Abstract $plugin,
                                       $message = null)
    {
        $this->assertSame($plugin, $event->getPlugin(), $message);
    }

    /**
     * Gets the events added to the handler by the plugin
     * @param string $type
     * @return array | null
     */
    public function getResponseEvents($type = null)
    {
        if (is_string($type) && strlen($type) > 0) {
            return $this->handler->getEventsOfType($type);
        }
        return $this->handler->getEvents();
    }

    /**
     * Sets the event for the test
     * @param array $event
     * @param array $eventArgs
     */
    public function setEvent(array $event, array $eventArgs = null)
    {
        $eventClass = 'Phergie_Event_Request';
        if (is_array($event)) {
            $eventClass = $event[0];
            $eventType  = $event[1];
        } else {
            throw new InvalidArgumentException("Invalid value for \$event");
        }
        $event = new $eventClass();
        $event->setType($eventType);
        $event->setArguements($eventArgs);
        $this->plugin->setEvent($event);
        $this->eventArgs = $eventArgs;
    }

    /**
     * Sets the plugin to be tested
     * If a plugin requries config for testing, an array placed in
     * $this->config will be parsed into a Phergie_Config object and
     * attached to the plugin
     */
    protected function setPlugin(Phergie_Plugin_Abstract $plugin)
    {
        $this->plugin = $plugin;
        $this->plugin->setEventHandler($this->handler);
        $this->plugin->setConnection($this->connection);
        $this->connection->setNick('test');
        if (!empty($this->config)) {
            $config = new Phergie_Config();
            foreach ($this->config as $configKey => $configValue) {
                $config[$configKey] = $configValue;
            }
            $plugin->setConfig($config);
        }
    }

    /**
     * Overrides the runTest method to add additional annotations
     * @return PHPUnit_Framework_TestResult
     */
    protected function runTest()
    {
        if (null === $this->plugin) {
            throw new RuntimeException(
                    'Tests cannot be run before plugin is set'
            );
        }
        
        // Clean the event handler... important!
        $this->handler->clearEvents();

        $info      = $this->getAnnotations();
        $event     = null;
        $eventArgs = array();
        if (isset($info['method']['event']) && isset($info['method']['event'][0])) {
            if (!is_string($info['method']['event'][0])) {
                throw new InvalidArgumentException(
                        'Only one event may be specified'
                );
            }
            $event = $info['method']['event'][0];

            if (stristr($event, '::')) {
                $event = explode('::', $event);
            }
        }
        if (isset($info['method']['eventArg'])) {
            $eventArgs = $info['method']['eventArg'];
        }
        if (null !== $event) {
            $this->setEvent($event, $eventArgs);
        }

        $testResult = parent::runTest();

        // Clean the event handler again... just incase this time.
        $this->handler->clearEvents();

        return $testResult;
    }

}
