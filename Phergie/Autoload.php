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
 * @package   Phergie
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2012 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie
 */

/**
 * Autoloader for Phergie classes.
 *
 * @category Phergie
 * @package  Phergie
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie
 */
class Phergie_Autoload
{
    /**
     * Prefixes to trim off of class names before trying to load their corresponding files.
     * Using this, Autoload can support loading class A_B_C from file folder/C.php (using prefix 'A_B_') instead of just
     * looking for it in the file folder/A/B/C.php.
     *
     * @var array
     */
     protected static $prefixes;

    /**
     * Constructor to add the base Phergie path to the include_path.
     *
     * @return void
     */
    public function __construct()
    {
        self::addPath(dirname(dirname(__FILE__)));
    }

    /**
     * Autoload callback for loading class files.
     *
     * @param string $class Class to load
     *
     * @return void
     */
    public function load($class)
    {
        $paths = explode(PATH_SEPARATOR, get_include_path());

        foreach ($paths as $path) {
            $fileName = "$class.php";
            if(isset(static::$prefixes[$path])) {
                $prefix = preg_quote(static::$prefixes[$path]);
                $fileName = preg_replace("{^$prefix}", '', $fileName);
            }
            $fileName = str_replace('_', DIRECTORY_SEPARATOR, $fileName);

            $filePath = $path . DIRECTORY_SEPARATOR . $fileName;
            if (file_exists($filePath)) {
                include $filePath;

                if (class_exists($class, false)
                    || interface_exists($class, false)
                ) {
                    return;
                }

                throw new Phergie_Exception(
                    'Expected class ' . $class
                    . ' in ' .  $filePath . ' not found'
                );
            }
        }
    }

    /**
     * Registers an instance of this class as an autoloader.
     *
     * @return void
     */
    public static function registerAutoloader()
    {
        spl_autoload_register(array(new self, 'load'));
    }

    /**
     * Add a path to the include path.
     *
     * @param string $path Path to add
     * @param string $prefix Prefix to trim off of the requested class name before trying to load the corresponding file
     *
     * @return void
     */
    public static function addPath($path, $prefix = null)
    {
        $includePath = get_include_path();
        $includePathList = explode(PATH_SEPARATOR, $includePath);
        if (!in_array($path, $includePathList)) {
            set_include_path($path . PATH_SEPARATOR . get_include_path());
            static::$prefixes[$path] = $prefix;
        }
    }
}
