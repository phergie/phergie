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
 * @package   Phergie_Plugin_Php
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2012 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Plugin_Php
 */

/**
 * Data source interface for the Php plugin.
 *
 * @category Phergie
 * @package  Phergie_Plugin_Php
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Plugin_Php
 * @uses     extension pdo
 * @uses     extension pdo_sqlite
 * @uses     Phergie_Plugin_Command pear.phergie.org
 */
interface Phergie_Plugin_Php_Source
{
    /**
     * Searches for a description of the function.
     *
     * @param string $function Search pattern to match against the function
     *        name, wildcards supported using %
     *
     * @return array|null Associative array containing the function name and
     *         description or NULL if no results are found
     */
    public function findFunction($function);
}
