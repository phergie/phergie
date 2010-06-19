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
 * @package   Phergie_Plugin_Command
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2010 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Plugin_Command
 */

/**
 * Database management class. Provides a base API for managing databases
 * within
 *
 * @category Phergie 
 * @package  Phergie_Plugin_Db_Manager
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Plugin_Db_Manager
 */
abstract class Phergie_Plugin_Db_Manager
{
    /**
     * PDO connection to the database
     *
     * @var PDO
     */
    private $_db;

    /**
     * gets the connection to the database
     *
     * @return PDO
     */
    public abstract function getDb();

    /**
     * checks if a table exists within the database
     * 
     * @param string $table_name table name to check for existance
     *
     * @return boolean
     * @abstract
     */
    public abstract function hasTable($table_name);
}
