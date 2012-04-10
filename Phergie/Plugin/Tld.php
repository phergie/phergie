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
 * @package   Phergie_Plugin_Tld
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2011 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Plugin_Tld
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
 * @uses     extension PDO
 * @uses     extension pdo_sqlite
 */
class Phergie_Plugin_Tld extends Phergie_Plugin_Abstract
{
    /**
     * Connection to the database
     *
     * @var PDO
     */
    protected $db;

    /**
     * Prepared statement for selecting a single TLD
     *
     * @var PDOStatement
     */
    protected $select;

    /**
     * Prepared statement for selecting all TLDs
     *
     * @var PDOStatement
     */
    protected $selectAll;

    /**
     * Checks for dependencies and sets up the database and hard-coded values.
     *
     * @return void
     */
    public function onLoad()
    {
        if (!extension_loaded('PDO') || !extension_loaded('pdo_sqlite')) {
            $this->fail('PDO and pdo_sqlite extensions must be installed');
        }

        $dbFile = $this->findDataFile('tld.db');
        try {
            $this->db = new PDO('sqlite:' . $dbFile);

            $this->select = $this->db->prepare(
                'SELECT type, description
                FROM tld
                WHERE LOWER(tld) = LOWER(:tld)'
            );

            $this->selectAll = $this->db->prepare(
                'SELECT tld, type, description
                FROM btld'
            );
        } catch (PDOException $e) {
            $this->getPluginHandler()->removePlugin($this);
        }
    }

    /**
     * takes a tld in the format '.tld' and returns its related data
     *
     * @param string $tld tld to process
     *
     * @return null
     */
    public function onCommandTld($tld)
    {
        $tld = ltrim($tld, '.');
        $description = $this->getTld($tld);
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
     * @return mixed Definition of the given TLD as a string or false if unknown
     */
    public function getTld($tld)
    {
        $tld = trim(strtolower($tld));
        if ($this->select->execute(array('tld' => $tld))) {
            $tlds = $this->select->fetch();
            if (is_array($tlds)) {
                return '(' . $tlds['type'] . ') ' . $tlds['description'];
            }
        }
        return false;
    }

    /**
     * Retrieves a list of all the TLDs and their definitions
     *
     * @return mixed Array of all the TLDs and their definitions or FALSE on
      *        failure
     */
    public function getTlds()
    {
        if ($this->selectAll->execute()) {
            $tlds = $this->selectAll->fetchAll();
            if (is_array($tlds)) {
                $tldinfo = array();
                foreach ($tlds as $key => $tld) {
                    if (!empty($tld['tld'])) {
                        $tldinfo[$tld['tld']] = "({$tld['type']}) "
                        . $tld['description'];
                    }
                }
                return $tldinfo;
            }
        }
        return false;
    }
}
