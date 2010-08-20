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

defined('PHERGIE_BASE_PATH') or define('PHERGIE_BASE_PATH', realpath(dirname(__FILE__) . '/../..'));

require_once PHERGIE_BASE_PATH . '/Phergie/Autoload.php';

/**
 * Unit test suite for Phergie_Autoload.
 *
 * @category Phergie
 * @package  Phergie_Tests
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Tests
 */
class Phergie_AutoloadTest extends PHPUnit_Framework_TestCase
{
    /**
     * SPL autoloader callbacks
     *
     * @var array
     */
    private $callbacks;

    /**
     * Old include path
     *
     * @var string
     */
    private $includePath;

    /**
     * Undoes effects of the test suite bootstrap that prevent reliable
     * testing of the autoloader.
     *
     * @return void
     */
    public function setUp()
    {
        $this->includePath = set_include_path('.');
        $this->callbacks = array();
        $functions = spl_autoload_functions();
        if (is_array($functions)) {
            foreach (spl_autoload_functions() as $callback) {
                $this->callbacks[] = $callback;
                spl_autoload_unregister($callback);
            }
        }
    }

    /**
     * Restores the effects of the test suite bootstrap for other test
     * suites.
     *
     * @return void
     */
    public function tearDown()
    {
        set_include_path($this->includePath);
        while ($callback = array_shift($this->callbacks)) {
            spl_autoload_register($callback);
        }
    }

    /**
     * Tests that the autoloader adds its containing directory to the
     * include path.
     *
     * @return void
     */
    public function testConstructorAddsToIncludePath()
    {
        $autoload = new Phergie_Autoload;
        $paths = explode(PATH_SEPARATOR, get_include_path());
        $this->assertContains(PHERGIE_BASE_PATH, $paths);
    }

    /**
     * Tests that the autoloader can successfully autoload a class.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testLoad()
    {
        $class = 'Phergie_Bot';
        $this->assertFalse(class_exists($class, false));
        $autoload = new Phergie_Autoload;
        $autoload->load($class);
        $this->assertTrue(class_exists($class, false));
    }

    /**
     * Tests that the autoloader can register itself as an autoloader.
     *
     * @return void
     */
    public function testRegisterAutoloader()
    {
        Phergie_Autoload::registerAutoloader();
        $this->assertEquals(
            1,
            count(spl_autoload_functions()),
            'Autoloader was not registered'
        );
    }

    /**
     * Tests that the autoloader can add a path to the include path.
     *
     * @return void
     */
    public function testAddPath()
    {
        $path = dirname(__FILE__);
        Phergie_Autoload::addPath($path);
        $paths = explode(PATH_SEPARATOR, get_include_path());
        $this->assertContains($path, $paths);
    }

    /**
     * Prevents preservation of global state in cases where test methods
     * must be run in separate processes.
     *
     * @param  PHPUnit_Framework_TestResult $result
     * @return PHPUnit_Framework_TestResult
     * @throws InvalidArgumentException
     */
    public function run(PHPUnit_Framework_TestResult $result = NULL)
    {
        $this->setPreserveGlobalState(false);
        return parent::run($result);
    }
}
