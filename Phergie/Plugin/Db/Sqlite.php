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
 * Provides basic management for SQLite databases
 *
 * @category Phergie
 * @package  Phergie_Plugin_Db_Sqlite
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Plugin_Db_Sqlite
 */

class Phergie_Plugin_Db_Sqlite extends Phergie_Plugin_Db_Manager
{

    /**
     * @var string database file name
     */
    private $_db_file;

    /**
     * @var string directory for database
     */
    private $_db_directory = '';

    /**
     * allows setting of the database file name when creating the class
     *
     * @param string $db_file database file name [optional]
     */
    public function __construct($db_file = null)
    {
        if ($db_file != null) {
            $this->setDbFile($db_file);
        }
    }

    /**
     * set the database file name
     * 
     * @param string $db_file sqlite database filename
     *
     * @return null
     */
    public function setDbFile($db_file)
    {
        if (is_string($db_file) && !empty($db_file)) {
            $this->_db_file = $db_file;
        }
    }

    /**
     * sets the directory to store the database file
     *
     * @param string $db_directory directory to use for the database
     *
     * @return null
     */
    public function setDbDirectory($db_directory)
    {
        if (is_string($db_directory) && !empty($db_directory)) {
            if (!is_dir($db_directory)) {
                if (!mkdir($db_directory, 0755, true)) {
                    throw new Phergie_Exception(
                            'Unable to create directory',
                            Phergie_Plugin_Db_Exception::
                            ERR_UNABLE_TO_CREATE_DIRECTORY
                    );
                }
            }

            $this->_db_directory = "{$db_directory}/";
        }
    }

    /**
     * gets the connection to the database
     * 
     * @return PDO
     */
    public function getDb()
    {
        if ($this->_db == null) {
            $this->_db = new PDO(
                    "sqlite:{$this->_db_directory}{$this->_db_file}"
            );
        }

        return $this->_db;
    }


    /**
     * checks if a table exists within the database
     *
     * @param string $table_name table name to check for existance
     *
     * @return boolean
     */
    public function hasTable($table_name)
    {
        if ($this->_db == null) {
            $this->getDb();
        }
        
        return (bool)$this->_db->query(
            'SELECT COUNT(*) FROM sqlite_master WHERE name = '
            . $this->_db->quote($table_name)
        )->fetchColumn();
    }
}