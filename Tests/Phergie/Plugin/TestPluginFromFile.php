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
 * Phergie_Plugin_Handler's addPath utility to be located and loaded.
 *
 * @category Phergie
 * @package  Phergie_Tests
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Tests
 */
class Phergie_Plugin_TestPluginFromFile extends Phergie_Plugin_Abstract
{
    /**
     * holds the arguments that were passed in to the constructor
     * @var array
     */
    protected $args;

    /**
     * processes a variable number of arguments into the args property
     *
     * @return null
     */
    public function __construct()
    {
        $this->args = func_get_args();
    }

    /**
     * returns the argument at the requested index that was stored
     * when passed in on class instantiation
     * 
     * @param int $index of the argument
     * 
     * @return mixed
     */
    public function getArg($index)
    {
        return @$this->args[$index];
    }
}
