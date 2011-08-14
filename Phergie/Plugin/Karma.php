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
 * @package   Phergie_Plugin_Karma
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2011 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Plugin_Karma
 */

/**
 * Handles requests for incrementation or decrementation of a maintained list
 * of counters for specified terms.
 *
 * @category Phergie
 * @package  Phergie_Plugin_Karma
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Plugin_Karma
 * @uses     extension PDO
 * @uses     extension pdo_sqlite
 * @uses     Phergie_Plugin_Command pear.phergie.org
 */
class Phergie_Plugin_Karma extends Phergie_Plugin_Abstract
{
    /**
     * SQLite object
     *
     * @var resource
     */
    protected $db = null;

    /**
     * Prepared statement to add a new karma record
     *
     * @var PDOStatement
     */
    protected $insertKarma;

    /**
     * Prepared statement to update an existing karma record
     *
     * @var PDOStatement
     */
    protected $updateKarma;

    /**
     * Retrieves an existing karma record
     *
     * @var PDOStatement
     */
    protected $fetchKarma;

    /**
     * Retrieves an existing fixed karma record
     *
     * @var PDOStatement
     */
    protected $fetchFixedKarma;

    /**
     * Retrieves a positive answer for a karma comparison
     *
     * @var PDOStatement
     */
    protected $fetchPositiveAnswer;

    /**
     * Retrieves a negative answer for a karma comparison
     *
     * @var PDOStatement
     */
    protected $fetchNegativeAnswer;

    /**
     * Check for dependencies and initializes a database connection and
     * prepared statements.
     *
     * @return void
     */
    public function onLoad()
    {
        $plugins = $this->getPluginHandler();
        $plugins->getPlugin('Command');
        $this->getDb();
    }

    /**
     * Initializes prepared statements used by the plugin.
     *
     * @return void
     */
    protected function initializePreparedStatements()
    {
        $this->fetchKarma = $this->db->prepare(
            'SELECT karma
            FROM karmas
            WHERE term = :term
            LIMIT 1'
        );

        $this->insertKarma = $this->db->prepare(
            'INSERT INTO karmas (term, karma)
            VALUES (:term, :karma)'
        );

        $this->updateKarma = $this->db->prepare(
            'UPDATE karmas
            SET karma = :karma
            WHERE term = :term'
        );

        $this->fetchFixedKarma = $this->db->prepare(
            'SELECT karma
            FROM fixed_karmas
            WHERE term = :term
            LIMIT 1'
        );

        $this->fetchPositiveAnswer = $this->db->prepare(
            'SELECT answer
            FROM positive_answers
            ORDER BY RANDOM()
            LIMIT 1'
        );

        $this->fetchNegativeAnswer = $this->db->prepare(
            'SELECT answer
            FROM negative_answers
            ORDER BY RANDOM()
            LIMIT 1'
        );
    }

    /**
     * Returns a connection to the plugin database, initializing one if none
     * is explicitly set.
     *
     * @return PDO Database connection
     */
    public function getDb()
    {
        if (empty($this->db)) {
            $defaultFileName = dirname(__FILE__) . '/Karma/karma.db';
            $fileName = $this->getConfig('karma.sqlite_db', $defaultFileName);

            if (!is_writable($fileName)) {
                throw new Phergie_Plugin_Exception(
                    'Cannot open the Karma SQLite DB: ' . $fileName . '. '
                    . 'Set this option with karma.sqlite_db.'
                );
            }

            $this->db = new PDO('sqlite:' . $fileName);
            $this->initializePreparedStatements();
        }
        return $this->db;
    }

    /**
     * Sets the connection to the plugin database, mainly intended for unit
     * testing.
     *
     * @param PDO $db Database connection
     *
     * @return Phergie_Plugin_Karma Provides a fluent interface
     */
    public function setDb(PDO $db)
    {
        $this->db = $db;
        $this->initializePreparedStatements();
        return $this;
    }

    /**
     * Get the canonical form of a given term.
     *
     * In the canonical form all sequences of whitespace
     * are replaced by a single space and all characters
     * are lowercased.
     *
     * @param string $term Term for which a canonical form is required
     *
     * @return string Canonical term
     */
    protected function getCanonicalTerm($term)
    {
        $canonicalTerm = strtolower(preg_replace('|\s+|', ' ', trim($term, '()')));
        switch ($canonicalTerm) {
        case 'me':
            $canonicalTerm = strtolower($this->event->getNick());
            break;
        case 'all':
        case '*':
        case 'everything':
            $canonicalTerm = 'everything';
            break;
        }
        return $canonicalTerm;
    }

    /**
     * Intercepts a message and processes any contained recognized commands.
     *
     * @return void
     */
    public function onPrivmsg()
    {
        $message = $this->getEvent()->getText();

        $termPattern = '\S+?|\([^<>]+?\)+';
        $actionPattern = '(?P<action>\+\+|--)';

        $modifyPattern = <<<REGEX
		{^
		(?J) # allow overwriting capture names
		\s*  # ignore leading whitespace

		(?:  # start with ++ or -- before the term
            $actionPattern
            (?P<term>$termPattern)
		|   # follow the term with ++ or --
            (?P<term>$termPattern)
			$actionPattern # allow no whitespace between the term and the action
		)
		$}ix
REGEX;

        $versusPattern = <<<REGEX
        {^
        	(?P<term0>$termPattern)
        		\s+(?P<method><|>)\s+
        	(?P<term1>$termPattern)$#
        $}ix
REGEX;

        $match = null;

        if (preg_match($modifyPattern, $message, $match)) {
            $action = $match['action'];
            $term = $this->getCanonicalTerm($match['term']);
            $this->modifyKarma($term, $action);
        } elseif (preg_match($versusPattern, $message, $match)) {
            $term0 = trim($match['term0']);
            $term1 = trim($match['term1']);
            $method = $match['method'];
            $this->compareKarma($term0, $term1, $method);
        }
    }

    /**
     * Get the karma rating for a given term.
     *
     * @param string $term Term for which the karma rating needs to be
     *        retrieved
     *
     * @return void
     */
    public function onCommandKarma($term)
    {
        $source = $this->getEvent()->getSource();
        $nick = $this->getEvent()->getNick();

        $canonicalTerm = $this->getCanonicalTerm($term);

        $fixedKarma = $this->fetchFixedKarma($canonicalTerm);
        if ($fixedKarma) {
            $message = $nick . ': ' . $term . ' ' . $fixedKarma;
            $this->doPrivmsg($source, $message);
            return;
        }

        $karma = $this->fetchKarma($canonicalTerm);

        $message = $nick . ': ';

        if ($term == 'me') {
            $message .= 'You have';
        } else {
            $message .= $term . ' has';
        }

        $message .= ' ';

        if ($karma) {
            $message .= 'karma of ' . $karma;
        } else {
            $message .= 'neutral karma';
        }

        $message .= '.';

        $this->doPrivmsg($source, $message);
    }

    /**
     * Resets the karma for a term to 0.
     *
     * @param string $term Term for which to reset the karma rating
     *
     * @return void
     */
    public function onCommandReincarnate($term)
    {
        $data = array(
            ':term' => $term,
            ':karma' => 0
        );
        $this->updateKarma->execute($data);
    }

    /**
     * Compares the karma between two terms. Optionally increases/decreases
     * the karma of either term.
     *
     * @param string $term0  First term
     * @param string $term1  Second term
     * @param string $method Comparison method (< or >)
     *
     * @return void
     */
    protected function compareKarma($term0, $term1, $method)
    {
        $event = $this->getEvent();
        $nick = $event->getNick();
        $source = $event->getSource();

        $canonicalTerm0 = $this->getCanonicalTerm($term0);
        $canonicalTerm1 = $this->getCanonicalTerm($term1);

        $fixedKarma0 = $this->fetchFixedKarma($canonicalTerm0);
        $fixedKarma1 = $this->fetchFixedKarma($canonicalTerm1);

        if ($fixedKarma0 || $fixedKarma1) {
            return;
        }

        if ($canonicalTerm0 == 'everything') {
            $change = $method == '<' ? '++' : '--';
            $karma0 = 0;
            $karma1 = $this->modifyKarma($canonicalTerm1, $change);
        } elseif ($canonicalTerm1 == 'everything') {
            $change = $method == '<' ? '--' : '++';
            $karma0 = $this->modifyKarma($canonicalTerm0, $change);
            $karma1 = 0;
        } else {
            $karma0 = $this->fetchKarma($canonicalTerm0);
            $karma1 = $this->fetchKarma($canonicalTerm1);
        }

        // Combining the first and second branches here causes an odd
        // single-line lapse in code coverage, but the lapse disappears if
        // they're separated
        if ($method == '<' && $karma0 < $karma1) {
            $replies = $this->fetchPositiveAnswer;
        } elseif ($method == '>' && $karma0 > $karma1) {
            $replies = $this->fetchPositiveAnswer;
        } else {
            $replies = $this->fetchNegativeAnswer;
        }
        $replies->execute();
        $reply = $replies->fetchColumn();

        if (max($karma0, $karma1) == $karma1) {
            list($canonicalTerm0, $canonicalTerm1)
                = array($canonicalTerm1, $canonicalTerm0);
        }

        $message = str_replace(
            array('%owner%','%owned%'),
            array($canonicalTerm0, $canonicalTerm1),
            $reply
        );

        $this->doPrivmsg($source, $message);
    }

    /**
     * Modifes a term's karma.
     *
     * @param string $term   Term to modify
     * @param string $action Karma action (either ++ or --)
     *
     * @return int Modified karma rating
     */
    protected function modifyKarma($term, $action)
    {
        $karma = $this->fetchKarma($term);
        if ($karma !== false) {
            $statement = $this->updateKarma;
        } else {
            $statement = $this->insertKarma;
        }

        $karma += ($action == '++') ? 1 : -1;

        $args = array(
            ':term'  => $term,
            ':karma' => $karma
        );
        $statement->execute($args);

        return $karma;
    }

    /**
     * Returns the karma rating for a specified term for which the karma
     * rating can be modified.
     *
     * @param string $term Term for which to fetch the corresponding karma
     *        rating
     *
     * @return integer|boolean Integer value denoting the term's karma or
     *         FALSE if there is the specified term has no associated karma
     *         rating
     */
    protected function fetchKarma($term)
    {
        $this->fetchKarma->execute(array(':term' => $term));
        $result = $this->fetchKarma->fetch(PDO::FETCH_ASSOC);

        if ($result === false) {
            return false;
        }

        return (int) $result['karma'];
    }

    /**
     * Returns a phrase describing the karma rating for a specified term for
     * which the karma rating is fixed.
     *
     * @param string $term Term for which to fetch the corresponding karma
     *        rating
     *
     * @return string Phrase describing the karma rating, which may be append
     *         to the term to form a complete response
     */
    protected function fetchFixedKarma($term)
    {
        $this->fetchFixedKarma->execute(array(':term' => $term));
        $result = $this->fetchFixedKarma->fetch(PDO::FETCH_ASSOC);

        if ($result === false) {
            return false;
        }

        return $result['karma'];
    }
}
