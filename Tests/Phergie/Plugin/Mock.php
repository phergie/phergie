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
 * Creates a plugin on the filesystem that can be used by
 * Phergie_Plugin_Handler::addPath() to be located and loaded.
 *
 * @category Phergie
 * @package  Phergie_Tests
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Tests
 */
class Phergie_Plugin_Mock extends Phergie_Plugin_Abstract
{
    /**
     * Arguments passed to the constructor
     *
     * @var array
     */
    protected $arguments;

    /**
     * Stores all arguments for later use.
     *
     * @return void
     */
    public function __construct()
    {
        $this->arguments = func_get_args();
    }

    /**
     * Returns all constructor arguments.
     *
     * @return array Enumerated array containing the arguments passed to the
     *         constructor in order
     */
    public function getArguments()
    {
        return $this->arguments;
    }
}
