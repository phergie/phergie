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
 * @package   Phergie_Plugin_Url
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2010 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Plugin_Url
 */

/**
 * Responds to a request for a TLD (formatted as .tld where tld is the TLD to
 * be looked up) with its corresponding description.
 *
 * @category Phergie 
 * @package  Phergie_Plugin_Tld
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Plugin_Tld
 * @uses     Phergie_Plugin_Http pear.phergie.org
 *
 * @pluginDesc Provides information for a top level domain.
 */
class Phergie_Plugin_Tld extends Phergie_Plugin_Command
{
    /**
     * static instance of this class
     *
     * @var Phergie_Plugin_Tld
     */
    protected static $instance;

    /**
     * connection to the database
     * @var PDO
     */
    protected $db;
    /**
     * Some fixed TLD values, keys must be lowercase
     * @var array
     */
    protected static $fixedTlds;

    /**
     * Prepared statement for selecting a single tld
     * @var PDOStatement
     */
    protected $select;

    /**
     * Prepared statement for selecting all tlds
     * @var PDOStatement
     */
    protected $selectAll;

    /**
     * Checks for dependencies, sets up database and hard coded values
     *
     * @return void
     */
    public function onLoad()
    {
        $help = $this->getPluginHandler()->getPlugin('Help');
        $help->register($this);

        if (!is_array(self::$fixedTlds)) {
            self::$fixedTlds = array(
                'phergie' => 'You can find Phergie at http://www.phergie.org',
                'spoon'   => 'Don\'t you know? There is no spoon!',
                'poo'     => 'Do you really think that\'s funny?',
                'root'    => 'Diagnostic marker to indicate '
                . 'a root zone load was not truncated.'
            );
        }

        try {
            $db_manager = new Phergie_Plugin_Db_Sqlite('tld.db');
            $db_manager->setDbDirectory(dirname(__FILE__) . '/Tld');
            $this->db = $db_manager->getDb();
            if (!$db_manager->hasTable('tld')) {
                $query = 'CREATE TABLE tld ('
                        . 'tld VARCHAR(20), '
                        . 'type VARCHAR(20), '
                        . 'description VARCHAR(255))';

                $this->db->exec($query);

                // prepare a statement to populate the table with
                // tld information
                $insert = $this->db->prepare(
                    'INSERT INTO tld
                    (tld, type, description)
                    VALUES (:tld, :type, :description)'
                );

                // grab tld data from iana.org...
                $contents = file_get_contents(
                    'http://www.iana.org/domains/root/db/'
                );

                // ...and then parse it out
                $regex = '{<tr class="iana-group[^>]*><td><a[^>]*>\s*\.?([^<]+)\s*'
                        . '(?:<br/><span[^>]*>[^<]*</span>)?</a></td><td>\s*'
                        . '([^<]+)\s*</td><td>\s*([^<]+)\s*}i';
                preg_match_all($regex, $contents, $matches, PREG_SET_ORDER);

                foreach ($matches as $match) {
                    list(, $tld, $type, $description) = array_pad($match, 4, null);
                    $type = trim(strtolower($type));
                    if ($type != 'test') {
                        $tld = trim(strtolower($tld));
                        $description = trim($description);

                        switch ($tld) {

                        case 'com':
                            $description = 'Commercial';
                            break;

                        case 'info':
                            $description = 'Information';
                            break;

                        case 'net':
                            $description = 'Network';
                            break;

                        case 'org':
                            $description = 'Organization';
                            break;

                        case 'edu':
                            $description = 'Educational';
                            break;

                        case 'name':
                            $description = 'Individuals, by name';
                            break;
                        }

                        if (empty($tld) || empty($description)) {
                            continue;
                        }

                        $regex = '{(^(?:Reserved|Restricted)\s*(?:exclusively\s*)?'
                                 . '(?:for|to)\s*(?:members of\s*)?(?:the|support)?'
                                 . '\s*|\s*as advised.*$)}i';
                        $description = preg_replace($regex, '', $description);
                        $description = ucfirst(trim($description));

                        $data = array_map(
                            'html_entity_decode', array(
                                'tld' => $tld,
                                'type' => $type,
                                'description' => $description
                            )
                        );

                        $insert->execute($data);
                    }
                }

                unset(
                    $insert,
                    $matches,
                    $match,
                    $contents,
                    $tld,
                    $type,
                    $description,
                    $data,
                    $regex
                );
            }

            // Create a prepared statements for retrieving TLDs
            $this->select = $this->db->prepare(
                'SELECT type, description '
                . 'FROM tld WHERE LOWER(tld) = LOWER(:tld)'
            );

            $this->selectAll = $this->db->prepare(
                'SELECT tld, type, description FROM tld'
            );
        } catch (PDOException $e) {
        }

        // Create a static instance of the class
        self::$instance = $this;
    }

    /**
     * takes a tld in the format '.tld' and returns its related data
     *
     * @param string $tld tld to process
     *
     * @return null
     *
     * @pluginCmd .[tld] request details about the tld
     */
    public function onCommandTld($tld)
    {
        // ensure first character of tld is '.'
        if (strpos($tld, '.') !== 0) {
            return;
        }

        // strip leading '.'
        $tld = substr($tld, 1);
        $description = self::getTld($tld);
        $this->doPrivmsg(
            $this->event->getSource(),
            "{$this->getEvent()->getNick()}: .{$tld} -> "
            . ($description ? $description : 'Unknown TLD')
        );
    }

    /**
     * Retrieves the definition for a given TLD if it exists
     *
     * @param string $tld TLD to search for
     * 
     * @return string Defination of the given TLD
     */
    public static function getTld($tld)
    {
        $tld = trim(strtolower($tld));
        if (isset(self::$fixedTlds[$tld])) {
            return self::$fixedTlds[$tld];
        } else if (self::$instance->db) {
            if (self::$instance->select->execute(array('tld' => $tld))) {
                $tlds = self::$instance->select->fetch();
                if (is_array($tlds)) {
                    return '(' . $tlds['type'] . ') ' . $tlds['description'];
                }
            }
        }
        return false;
    }

    /**
     * Retrieves a list of all the TLDs and their definations
     *
     * @return array Array of all the TLDs and their definations
     */
    public static function getTlds()
    {
        if (self::$instance->db && self::$instance->selectAll->execute()) {
            $tlds = self::$instance->selectAll->fetchAll();
            if (is_array($tlds)) {
                $tldinfo = array();
                foreach ($tlds as $key => $tld) {
                    if (!empty($tld['tld'])) {
                        $tldinfo[$tld['tld']] = "({$tld['type']}) "
                        . $tld['description'];
                    }
                }
                unset($tlds);
                return $tldinfo;
            }
        }
        return false;
    }
}

