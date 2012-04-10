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
 * @copyright 2008-2011 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Tests
 */

/**
 * TestCase listener that records benchmarks for each test method and
 * outputs them when the execution of all test methods is concluded.
 *
 * @category Phergie
 * @package  Phergie_Tests
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Tests
 */
class Phergie_TestCase_BenchmarkListener implements PHPUnit_Framework_TestListener
{
    protected $benchmarks = array();

    public function endTest(PHPUnit_Framework_Test $test, $time)
    {
        $this->benchmarks[$test->getName()] = $time;
    }

    public function endTestSuite(PHPUnit_Framework_TestSuite $suite)
    {
        if (count($this->benchmarks) == count($suite)) {
            echo PHP_EOL, PHP_EOL, 'Benchmarks:';
            natsort($this->benchmarks);
            $this->benchmarks = array_reverse($this->benchmarks, true);
            foreach ($this->benchmarks as $name => $time) {
                echo PHP_EOL, $name, ': ', number_format($time, 3), ' s';
            }
        }
    }

    public function startTest(
        PHPUnit_Framework_Test $test
    ) {
    }

    public function addError(
        PHPUnit_Framework_Test $test,
        Exception $e,
        $time
    ) {
    }

    public function addFailure(
        PHPUnit_Framework_Test $test,
        PHPUnit_Framework_AssertionFailedError $e,
        $time
    ) {
    }

    public function addIncompleteTest(
        PHPUnit_Framework_Test $test,
        Exception $e,
        $time
    ) {
    }

    public function addSkippedTest(
        PHPUnit_Framework_Test $test,
        Exception $e,
        $time
    ) {
    }

    public function startTestSuite(
        PHPUnit_Framework_TestSuite $suite
    ) {
    }
}
