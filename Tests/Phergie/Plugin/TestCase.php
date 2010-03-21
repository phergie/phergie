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
    protected $_handler;

    /**
     * @var Phergie_Connection
     */
    protected $_connection;

    /**
     * @var array
     */
    protected $eventArgs;

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
     */
    public function assertHasEvent($event)
    {
        self::assertTrue($this->handler->hasEventOfType($event));
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
     */
    protected function setPlugin(Phergie_Plugin_Abstract $plugin)
    {
        $this->plugin = $plugin;
        $this->plugin->setEventHandler($this->handler);
        $this->plugin->setConnection($this->connection);
    }

    /**
     * Overrides the runTest method to add additional annotations
     * @return PHPUnit_Framework_TestResult
     */
    protected function runTest()
    {
        // Clean the event handler... important!
        $this->handler->clearEvents();

        if (null === $this->plugin) {
            throw new RuntimeException(
                    'Tests cannot be run before plugin is set'
            );
        }
        if ($this->name === NULL) {
            throw new PHPUnit_Framework_Exception(
              'PHPUnit_Framework_TestCase::$name must not be NULL.'
            );
        }

        try {
            $class     = new ReflectionClass($this);
            $method    = $class->getMethod($this->name);
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
            
        } catch (ReflectionException $e) {
            $this->fail($e->getMessage());
        }

        try {
            $testResult = $method->invokeArgs(
              $this, array_merge($this->data, $this->dependencyInput)
            );
        } catch (Exception $e) {
            if (!$e instanceof PHPUnit_Framework_IncompleteTest &&
                !$e instanceof PHPUnit_Framework_SkippedTest &&
                is_string($this->expectedException) &&
                $e instanceof $this->expectedException) {
                if (is_string($this->expectedExceptionMessage) &&
                    !empty($this->expectedExceptionMessage)) {
                    $this->assertContains(
                      $this->expectedExceptionMessage,
                      $e->getMessage()
                    );
                }

                if (is_int($this->expectedExceptionCode) &&
                    $this->expectedExceptionCode !== 0) {
                    $this->assertEquals(
                      $this->expectedExceptionCode, $e->getCode()
                    );
                }

                $this->numAssertions++;

                return;
            } else {
                throw $e;
            }
        }

        if ($this->expectedException !== NULL) {
            $this->numAssertions++;
            $this->fail('Expected exception ' . $this->expectedException);
        }

        return $testResult;
    }

}