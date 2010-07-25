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
 * @package   Phergie_Plugin_Lart
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2010 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Plugin_Lart
 */

/**
 * Accepts terms and corresponding definitions for storage to a local data
 * source and performs and returns the result of lookups for term definitions
 * as they are requested.
 *
 * @category Phergie
 * @package  Phergie_Plugin_Lart
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Plugin_Lart
 * @uses     Phergie_Plugin_Command pear.phergie.org
 * @uses     extension PDO
 * @uses     extension pdo_sqlite
 */
class Phergie_Plugin_Lart extends Phergie_Plugin_Abstract
{
    /**
     * PDO instance for the database
     *
     * @var PDO
     */
    protected $db;

    /**
     * Prepared statement for inserting a new definition
     *
     * @var PDOStatement
     */
    protected $save;

    /**
     * Prepared statement for deleting the definition for a given term
     *
     * @var PDOStatement
     */
    protected $delete;

    /**
     * Prepared statement for searching for a definition for which the term
     * matches as a regular expression against a given search string
     *
     * @var PDOStatement
     */
    protected $process;

    /**
     * Prepared statement for searching for a definition by its exact term
     *
     * @var PDOStatement
     */
    protected $select;

    /**
     * Checks for dependencies and initializes the database.
     *
     * @return void
     */
    public function onLoad()
    {
        if (!extension_loaded('PDO') || !extension_loaded('pdo_sqlite')) {
            $this->fail('PDO and pdo_sqlite extensions must be installed');
        }

        $this->plugins->getPlugin('Command');

        $dir = dirname(__FILE__) . '/' . $this->getName();
        $path = $dir . '/lart.db';
        $exists = file_exists($path);
        if (!$exists) {
            mkdir($dir);
        }

        try {
            $this->db = new PDO('sqlite:' . $path);
        } catch (PDO_Exception $e) {
            throw new Phergie_Plugin_Exception($e->getMessage());
        }

        $this->db->sqliteCreateFunction('preg_match', 'preg_match');

        if (!$exists) {
            $this->db->exec('
                CREATE TABLE lart (
                    name VARCHAR(255),
                    definition TEXT,
                    hostmask VARCHAR(50),
                    tstamp VARCHAR(19)
                )
            ');
            $this->db->exec('
                CREATE UNIQUE INDEX lart_name ON lart (name)
            ');
        }

        $this->save = $this->db->prepare('
            REPLACE INTO lart (name, definition, hostmask, tstamp)
            VALUES (:name, :definition, :hostmask, :tstamp)
        ');

        $this->process = $this->db->prepare('
            SELECT *
            FROM lart
            WHERE preg_match(name, :name)
        ');

        $this->select = $this->db->prepare('
            SELECT *
            FROM lart
            WHERE name = :name
        ');

        $this->delete = $this->db->prepare('
            DELETE FROM lart
            WHERE name = :name
        ');
    }

    /**
     * Retrieves the definition for a given term if it exists.
     *
     * @param string $term Term to search for
     *
     * @return mixed String containing the definition or FALSE if no definition
     *               exists
     */
    protected function getLart($term)
    {
        $this->process->execute(array(':name' => $term));
        $row = $this->process->fetchObject();
        if ($row === false) {
            return false;
        }
        preg_match($row->name, $term, $match);
        $definition = preg_replace(
            "/(?:\\\\|\\$)([0-9]+)/e",
            '$match[\1]',
            $row->definition
        );
        $event = $this->getEvent();
        $definition = str_replace(
            array('$source', '$nick'),
            array($event->getSource(), $event->getNick()),
            $definition
        );
        return $definition;
    }

    /**
     * Deletes a given definition.
     *
     * @param string $term Term for which the definition should be deleted
     *
     * @return boolean TRUE if the definition was found and deleted, FALSE
     *         otherwise
     */
    protected function deleteLart($term)
    {
        $this->delete->execute(array(':name' => $term));
        return ($this->delete->rowCount() > 0);
    }

    /**
     * Saves a given definition.
     *
     * @param string $term       Term to trigger a response containing the
     *        corresponding definition, may be a regular expression
     * @param string $definition Definition corresponding to the term
     *
     * @return boolean TRUE if the definition was saved successfully, FALSE
     *         otherwise
     */
    protected function saveLart($term, $definition)
    {
        $data = array(
            ':name' => $term,
            ':definition' => $definition,
            ':hostmask' => (string) $this->getEvent()->getHostmask(),
            ':tstamp' => time()
        );
        $this->save->execute($data);
        return ($this->save->rowCount() > 0);
    }

    /**
     * Returns information about a definition.
     *
     * @param string $term Term about which to return information
     *
     * @return void
     */
    public function onCommandLartinfo($term)
    {
        $this->select->execute(array(':name' => $term));
        $row = $this->select->fetchObject();
        $msg = $this->getEvent()->getNick() . ': ';
        if (!$row) {
            $msg .= 'Lart not found';
        } else {
            $msg .= 'Term: ' . $row->name
                . ', Definition: ' . $row->definition
                . ', User: ' . $row->hostmask
                . ', Added: ' . date('n/j/y g:i A', $row->tstamp);
        }
        $this->doNotice($this->getEvent()->getSource(), $msg);
    }

    /**
     * Creates a new definition.
     *
     * @param string $term       Term to add
     * @param string $definition Definition to add
     *
     * @return void
     */
    public function onCommandAddlart($term, $definition)
    {
        $result = $this->saveLart($term, $definition);
        if ($result) {
            $msg = 'Lart saved successfully';
        } else {
            $msg = 'Lart could not be saved';
        }
        $this->doNotice($this->getEvent()->getSource(), $msg);
    }

    /**
     * Removes an existing definition.
     *
     * @param string $term Term for which the definition should be removed
     *
     * @return void
     */
    public function onCommandDeletelart($term)
    {
        $source = $this->getEvent()->getSource();
        if ($this->deleteLart($term)) {
            $msg = 'Lart deleted successfully';
        } else {
            $msg = 'Lart not found';
        }
        $this->doNotice($source, $msg);
    }

    /**
     * Processes definition triggers in the text of the current event.
     *
     * @return void
     */
    protected function processLart()
    {
        $lart = $this->getLart($this->getEvent()->getText());
        if ($lart) {
            if (strpos($lart, '/me') === 0) {
                $lart = substr($lart, 4);
                $method = 'doAction';
            } else {
                $method = 'doPrivmsg';
            }
            $this->$method($this->getEvent()->getSource(), $lart);
        }
    }

    /**
     * Processes definition triggers in messages.
     *
     * @return void
     */
    public function onPrivmsg()
    {
        $this->processLart();
    }

    /**
     * Processes definition triggers in CTCP actions.
     *
     * @return void
     */
    public function onAction()
    {
        $this->processLart();
    }
}
