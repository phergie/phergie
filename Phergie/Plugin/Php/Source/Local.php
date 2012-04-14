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
 * Data source for {@see Phergie_Plugin_Php}. This source reads function
 * descriptions from a file and stores them in a SQLite database. When a
 * function description is requested, the function is retrieved from the
 * local database.
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
class Phergie_Plugin_Php_Source_Local implements Phergie_Plugin_Php_Source
{
    /**
     * Local database for storage
     *
     * @var PDO
     */
    protected $database;

    /**
     * Source of the PHP function summary
     *
     * @var string
     */
    protected $url = 'http://svn.php.net/repository/phpdoc/doc-base/trunk/funcsummary.txt?revision=HEAD';

    /**
     * Constructor to initialize the data source.
     *
     * @param string $path Path to the SQLite database to use
     * @return void
     */
    public function __construct($path)
    {
        try {
            $this->database = new PDO('sqlite:' . $path);
            $this->buildDatabase();
            // @todo Modify this to be rethrown as an appropriate
            //       Phergie_Plugin_Exception and handled in Phergie_Plugin_Php
        } catch (PDOException $e) {
            throw new Phergie_Plugin_Php_Source_Exception(
                'PDO failure: ' . $e->getMessage()
            );
        }
    }

    /**
     * Searches for a description of the function.
     *
     * @param string $function Search pattern to match against the function
     *        name, wildcards supported using %
     *
     * @return array|null Associative array containing the function name and
     *         description or NULL if no results are found
     */
    public function findFunction($function)
    {
        // Remove possible parentheses
        $split = preg_split('{\(|\)}', $function);
        $function = (count($split)) ? array_shift($split) : $function;

        // Prepare the database statement
        $stmt = $this->database->prepare(
            'SELECT `name`, `description`
             FROM `functions` WHERE `name` LIKE :function'
        );

        $stmt->execute(array(':function' => $function));

        // Check the results
        if (count($stmt) > 0) {
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
     * @param bool $rebuild TRUE to force a rebuild of the table used to
     *        house function information, FALSE otherwise, defaults to FALSE
     *
     * @return void
     */
    protected function buildDatabase($rebuild = false)
    {
        // Check to see if the functions table exists
        $checkstmt = $this->database->query(
            "SELECT COUNT(*) FROM `sqlite_master` WHERE `name` = 'functions'"
        );

        $checkstmt->execute();
        $result = $checkstmt->fetch(PDO::FETCH_ASSOC);
        unset( $checkstmt );
        $table = $result['COUNT(*)'];
        unset( $result );
        // If the table doesn't exist, create it
        if (!$table) {
                $this->database->exec(
                    'CREATE TABLE `functions`
                     (`name` VARCHAR(255), `description` TEXT)'
                );
                $this->database->exec(
                    'CREATE UNIQUE INDEX `functions_name` ON `functions` (`name`)'
                );
        }

        // If we created a new table, fill it with data
        if (!$table || $rebuild) {
            // Get the contents of the source file
            // @todo Handle possible error cases better here; the @ operator
            //       shouldn't be needed
            $contents = @file(
                $this->url,
                FILE_IGNORE_NEW_LINES + FILE_SKIP_EMPTY_LINES
            );

            if (!$contents) {
                return;
            }

            // Parse the contents
            $valid = array();
            $firstPart = '';
            $lineNumber = 0;
            foreach ($contents as $line) {
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
                } else {
                    // ... it's the last part of the complete function description
                    $completeLine = $firstPart . ' ' . $line;
                    $firstPart = '';
                    $tmpregex = '{^([^\s]*)[\s]?([^)]*)\(([^\)]*)\)[\sU]+'
                        . '([\sa-zA-Z0-9\.,\-_()]*)$}';
                    if (preg_match($tmpregex, $completeLine, $matches)) {
                        $valid[] = $matches;
                    }
                }
                // Up the line number before going to the next line
                $lineNumber++;
            }
            // free up some memory
            unset($contents);

            // Process the valid matches
            if (count($valid) > 0) {
                // Clear the database
                $this->database->exec('DELETE * FROM `functions`');

                // Prepare the sql statement
                $stmt = $this->database->prepare(
                    'INSERT INTO `functions` (`name`, `description`)
                    VALUES (:name, :description)'
                );
                $this->database->beginTransaction();

                // Insert the data
                foreach ($valid as $function) {
                    // Extract function values
                    list( , $retval, $name, $params, $desc) = $function;
                    if (empty($name)) {
                        $name = $retval;
                        $retval = '';
                    }
                    // Reconstruct the complete function line
                    $line = trim(
                        $retval . ' ' . $name . '(' . $params . ') - ' . $desc
                    );

                    // Execute the statement
                    $stmt->execute(
                        array(':name' => $name, ':description' => $line)
                    );
                }

                // Commit the changes to the database
                $this->database->commit();
            }
            // free up some more memory
            unset($valid);
        }
    }
}
