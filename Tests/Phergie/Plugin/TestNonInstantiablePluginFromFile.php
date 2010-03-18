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
 * @package   Tests
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2010 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Plugin_Ping
 */

/**
 * Creates a plugin on the filesystem that can be used by
 * Phergie_Plugin_Handler's addPath utility to be located and loaded
 * This spe
 *
 * @category Phergie
 * @package  Tests
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Plugin_Ping
 */
class Phergie_Plugin_TestNonInstantiablePluginFromFile
extends Phergie_Plugin_Abstract
{
    /**
     * private constructor ensures that this class is not instantiable
     */
    private function __construct()
    {

    }
}
