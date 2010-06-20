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
 * @copyright 2008-2010 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Command
 */

/**
 * Database management class. Provides a base API for managing databases
 * within
 *
 * @category Phergie
 * @package  Phergie
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Db_Manager
 */
abstract class Phergie_Db_Manager
{
    /**
     * Returns a connection to the database.
     *
     * @return object
     */
    public abstract function getDb();

    /**
     * Checks if a table/collection exists within the database.
     *
     * @param string $table Table/collection name to check for
     *
     * @return boolean
     */
    public abstract function hasTable($table);
}
