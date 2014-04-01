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
 * @package   Phergie_Plugin_Db
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2012 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Plugin_Db
 */

/**
 * Helper plugin for common database operations.
 *
 * @category Phergie
 * @package  Phergie_Plugin_Db
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Plugin_Db
 * @uses     extension PDO
 * @uses     extension pdo_sqlite
 */

class Phergie_Plugin_Db extends Phergie_Plugin_Abstract
{
    const DEBUG = true;

    /**
     *  Checks to see if the necessary extensions are loaded.
     *
     *  @return void
     */
    public function onLoad()
    {
        $this->_doesPdoExist();
    }

    /**
     *  Validates that the Pdo Extenstion Exists.
     *
     *  @return void
     */
    private function _doesPdoExist()
    {
        if (!extension_loaded('PDO') || !extension_loaded('pdo_sqlite')) {
            $this->fail('PDO and pdo_sqlite extensions must be installed');
        }
    }

    /**
     *  Initializes database and creates directories if needed
     *
     *  @param string $directory  plugin name
     *  @param string $dbFile     database name
     *  @param string $schemaFile schema filename
     *
     *  @return object
     */
    public function init($directory, $dbFile, $schemaFile)
    {
        // We set the directory to the current path.
        if (substr(dirname(__FILE__), -1) == '/') {
                $resource_directory = dirname(__FILE__) . $directory;
        } else {
                $resource_directory = dirname(__FILE__) . '/' . $directory;
        }
        // Support alternate path for centralized db storage
        if ($this->getConfig('dbpath')) {
            echo "DEBUG: Switching to alternate DB path - $directory\n";

            if (is_dir($this->getConfig('dbpath'))) {
                $directory = $this->getConfig('dbpath') . $directory;

                if (!is_dir($directory)) {
                    mkdir($directory); // make directory
                }
            } else {
                $this->fail('Unable to create Database(s)');
            }
        } else {
            $directory = $resource_directory;
        }
        $this->isResourceDirectory($directory);
        $dbFile = $directory . $dbFile; // Add the directory path
        $schemaFile = $resource_directory . $schemaFile; // Add path
        try {
            $db = new PDO('sqlite:' . $dbFile);
        } catch (PDO_Exception $e) {
            throw new Phergie_Plugin_Exception($e->getMessage());
        }

        $this->createTablesFromSchema($db, $schemaFile);
        return $db;
    }

    /**
     * fully specified file name as string
     * used to check that the resource directory does exist
     *
     * @param string $directory Directory to check
     *
     * @return void
     *
     */
    public function isResourceDirectory($directory)
    {
        if (!is_dir($directory)) {
            $this->fail('The Resource directory: ' . $directory . ' does not exist');
        }
    }

    /**
     * fully specified file name as string
     * used to check that the schema file does exist
     *
     * @param string $file File to check
     *
     * @return void
     *
     */
    public function isSchemaFile($file)
    {
        if (!is_readable($file)) {
            $this->fail(
                'The schema file: ' . $file
                . ' is not readable or does not exist'
            );
        }
    }

    /**
     * Supply sql statement and one word type parameter
     *  (IE, create, update, insert, delete)
     * and the method will validate that the sql contains that syntax
     *
     * @param string $sql  TODO Desc
     * @param string $type TODO Desc
     *
     * @return bool
     */
    public function validateSqlType($sql, $type)
    {
        preg_match('/^'.$type.'/i', $sql, $matches);
        return ($matches[0]) ? true : false;
    }

    /**
     *  Creates database table
     *
     *  @param String $db  database reference
     *  @param String $sql create table sql statement
     *
     *  @return void
     */
    public function createTable($db, $sql)
    {
        if (!$this->validateSqlType($sql, 'create')) {
            $this->fail('The SQL provided is not a create statement');
        }

        try {
            $db->exec($sql);
        } catch (PDO_Exception $e) {
            throw new Phergie_Plugin_Exception($e->getMessage());
        }
    }

    /**
     * Loads the schema file into array then searches
     * for each table and if not found creates the table
     *
     * @param String $db   Database Table
     * @param String $file Filename
     *
     * @return void
     */
    public function createTablesFromSchema($db, $file)
    {
        $this->isSchemaFile($file);
        $file = strtolower(file_get_contents($file));
        preg_match_all('/create\stable\s([a-z_]+)[^;.]+/s', $file, $matches);

        if (count($matches[0]) != count($matches[1])) {
            $this->fail(
                'Schema array key value mismatch, '
                . 'the regular expression must not be working correctly'
            );
        }

        $tables = array_combine($matches[1], $matches[0]);

        foreach ($tables as $name => $sql) {
            if (!$this->hasTable($db, $name)) {
                $this->createTable($db, $sql);
            }
        }
    }

    /**
     *  Validates that database table exists
     *
     *  @param String $db   database name
     *  @param String $name table name
     *
     *  @return bool
     */
    public function hasTable($db, $name)
    {
        $sql = 'SELECT COUNT(*)
            FROM sqlite_master
            WHERE name = :tableName';

        $statement = $db->prepare($sql);
        $statement->execute(array(':tableName' => $db->quote($name)));
        return (bool) $statement->fetchColumn();
    }

    /**
     * Drops a table
     *
     *  TODO Desc
     *
     * @param string $db   Database name
     * @param string $name Table name
     *
     * @return bool True on success
     */
    public function dropTable($db, $name)
    {
        $statement = $db->prepare('DROP TABLE :name;');
        return (bool) $statement->execute(array(':name' => $db->quote($name)));
    }

    /**
     * Jared's crap debug method
     *
     * @param String $message TODO Desc
     *
     * @return void
     */
    private function _debug($message)
    {
        if (self::DEBUG) {
            echo 'DEBUG: ['. date('c') . '] - '. $message . "\n";
        }
    }
}
