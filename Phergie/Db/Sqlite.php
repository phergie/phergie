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
 * @link      http://pear.phergie.org/package/Phergie
 */

/**
 * Provides basic management for SQLite databases
 *
 * @category Phergie
 * @package  Phergie
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie
 */
class Phergie_Db_Sqlite extends Phergie_Db_Manager
{
    /**
     * Database connection
     *
     * @var PDO
     */
    protected $db;

    /**
     * Database file path
     *
     * @var string
     */
    protected $dbFile;

    /**
     * Allows setting of the database file path when creating the class.
     *
     * @param string $dbFile database file path (optional)
     *
     * @return void
     */
    public function __construct($dbFile = null)
    {
        if ($dbFile != null) {
            $this->setDbFile($dbFile);
        }
    }

    /**
     * Sets the database file path.
     *
     * @param string $dbFile SQLite database file path
     *
     * @return null
     */
    public function setDbFile($dbFile)
    {
        if (is_string($dbFile) && !empty($dbFile)) {
            $this->dbFile = $dbFile;
        }
    }

    /**
     * Returns a configured database connection.
     *
     * @return PDO
     */
    public function getDb()
    {
        if (!empty($this->db)) {
            return $this->db;
        }

        $dir = dirname($this->dbFile);
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            throw new Phergie_Db_Exception(
                'Unable to create directory',
                Phergie_Db_Exception::ERR_UNABLE_TO_CREATE_DIRECTORY
            );
        }

        $this->db = new PDO('sqlite:' . $this->dbFile);

        return $this->db;
    }


    /**
     * Returns whether a given table exists in the database.
     *
     * @param string $table Name of the table to check for
     *
     * @return boolean TRUE if the table exists, FALSE otherwise
     */
    public function hasTable($table)
    {
        $db = $this->getDb();

        return (bool) $db->query(
            'SELECT COUNT(*) FROM sqlite_master WHERE name = '
            . $db->quote($table)
        )->fetchColumn();
    }
}
