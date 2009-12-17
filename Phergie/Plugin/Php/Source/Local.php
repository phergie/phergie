<?php

require_once 'Phergie/Plugin/Php/Source.php';

/**
 * Data source for {@see Phergie_Plugin_Php}.
 * This source reads function descriptions from a file and stores them in a 
 * SQLite database. When a function description is requested the function is
 * retrieved from the local database.
 */
class Phergie_Plugin_Php_Source_Local implements Phergie_Plugin_Php_Source
{
    /**
     * Local database for storage
     *
     * @var resource
     */
    protected $_database;

    /**
     * Source of the PHP function summary
     *
     * @var string
     */
    protected $_url = 'http://cvs.php.net/viewvc.cgi/phpdoc/funcsummary.txt?revision=HEAD';

    /**
     * Constructor to initialize the data source.
     *
     * @return void
     */
    public function __construct()
    {
        $path = dirname(__FILE__);

        try {
            $this->_database = new PDO('sqlite:' . $path . '/functions.db');
            $this->_buildDatabase();
        } catch (PDOException $e) { }
    }

    /**
     * Searches for a description of the function.
     * 
     * @param string $function
     * @return array|null
     */
    public function findFunction($function)
    {
        // Remove possible parentheses
        $split = preg_split('{\(|\)}', $function);
        $function = (count($split)) ? array_shift($split) : $function;

        // Prepare the database statement
        $stmt = $this->_database->prepare('SELECT `name`, `description` FROM `functions` WHERE `name` LIKE :function');
        $stmt->execute(array(':function' => $function));

        // Check the results
        if(count($stmt) > 0) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            /**
             * @todo add class and function URLS
             * class methods: http://php.net/manual/en/classname.methodname.php
             * functions: http://php.net/manual/en/function.functionname.php
             * where '_' is replaced with '-'
             */
            return $result;
        }

        // No results found, return
        return null;
    }

    /**
     * Build the database and parses the function summary file into it.
     *
     * @param bool $rebuild
     */
    protected function _buildDatabase($rebuild = false)
    {
        // Check to see if the functions table exists
        $table = $this->_database->exec("SELECT COUNT(*) FROM `sqlite_master` WHERE `name` = 'functions'");
        
        // If the table doesn't exist, create it
        if(!$table) {
            $this->_database->exec('CREATE TABLE `functions` (`name` VARCHAR(255), `description` TEXT)');
            $this->_database->exec('CREATE UNIQUE INDEX `functions_name` ON `functions` (`name`)');
        }

        // If we created a new table, fill it with data
        if(!$table || $rebuild) {
            // Get the contents of the source file
            $contents = @file($this->_url, FILE_IGNORE_NEW_LINES + FILE_SKIP_EMPTY_LINES);

            if(!$contents) {
                return;
            }
            
            // Parse the contents
            $valid = array();
            $firstPart = '';
            $lineNumber = 0;
            foreach($contents as $line) {
                // Clean the current line
                $line = trim($line);

                // Skip comment lines
                if (0 === strpos($line, '#')) {
                    // reset the line if the current line is odd
                    if (($lineNumber % 2) !== 0) {
                        $lineNumber--;
                    }
                    continue;
                }

                /*
                 * If the current line is even, it's the first part of the
                 * complete function description ...
                 */
                if (($lineNumber % 2) === 0) {
                    $firstPart = $line;
                }
                // ... it's the last part of the complete function description
                else {
                    $completeLine = $firstPart . ' ' . $line;
                    $firstPart = '';
                    if(preg_match('{^([^\s]*)[\s]?([^)]*)\(([^\)]*)\)[\sU]+([\sa-zA-Z0-9\.\-_]*)$}', $completeLine, $matches)) {
                        $valid[] = $matches;
                    }
                }
                // Up the line number before going to the next line
                $lineNumber++;
            }
            // free up some memory
            unset($contents);

            // Process the valid matches
            if(count($valid) > 0) {
                // Clear the database
                $this->_database->exec('DELETE * FROM `functions`');

                // Prepare the sql statement
                $stmt = $this->_database->prepare('INSERT INTO `functions` (`name`, `description`) VALUES (:name, :description)');
                $this->_database->beginTransaction();

                // Insert the data
                foreach($valid as $function) {
                    // Extract function values
                    list( , $retval, $name, $params, $desc) = $function;
                    if(empty($name)) {
                        $name = $retval;
                        $retval = '';
                    }
                    // Reconstruct the complete function line
                    $line = trim($retval . ' ' . $name . '(' . $params . ') - ' . $desc);
                    // Execute the statement
                    $stmt->execute(array(':name' => $name, ':description' => $line));
                }
                
                // Commit the changes to the database
                $this->_database->commit();
            }
            // free up some more memory
            unset($valid);
        }
    }

}
