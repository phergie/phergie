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
 * @copyright 2008-2010 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Plugin_Karma
 */

/**
 * Handles requests for incrementation or decrementation of a maintained list
 * of counters for specified terms and antithrottling to prevent extreme
 * inflation or depression of counters by any single individual.
 *
 * @category Phergie
 * @package  Phergie_Plugin_Karma
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Plugin_Karma
 */
class Phergie_Plugin_Karma extends Phergie_Plugin_Abstract
{
    /**
     * Stores the SQLite object
     *
     * @var resource
     */
    protected $db = null;

    /**
     * Retains the last garbage collection date
     *
     * @var array
     */
    protected $lastGc = null;

    /**
     * Logs the karma usages and limits users to one karma change per word
     * and per day
     *
     * @return void
     */
    protected $log = array();

    /**
     * Some fixed karma values, keys must be lowercase
     *
     * @var array
     */
    protected $fixedKarma;

    /**
     * A list of blacklisted values
     *
     * @var array
     */
    protected $karmaBlacklist;

    /**
     * Answers for correct assertions
     */
    protected $positiveAnswers;

    /**
     * Answers for incorrect assertions
     */
    protected $negativeAnswers;

    /**
     * Prepared PDO statements
     *
     * @var PDOStatement
     */
    protected $insertKarma;
    protected $updateKarma;
    protected $fetchKarma;
    protected $insertComment;

    /**
     * Connects to the database containing karma ratings and initializes
     * class properties.
     *
     * @return void
     */
    public function onLoad()
    {
        $this->db = null;
        $this->lastGc = null;
        $this->log = array();

        if(!defined('M_EULER')) {
            define('M_EULER', '0.57721566490153286061');
        }

        $this->fixedKarma = array(
            'phergie'      => '%s has karma of awesome',
            'pi'           => '%s has karma of ' . M_PI,
            'Î '            => '%s has karma of ' . M_PI,
            'Ï€'            => '%s has karma of ' . M_PI,
            'chucknorris'  => '%s has karma of Warning: Integer out of range',
            'chuck norris' => '%s has karma of Warning: Integer out of range',
            'c'            => '%s has karma of 299 792 458 m/s',
            'e'            => '%s has karma of ' . M_E,
            'euler'        => '%s has karma of ' . M_EULER,
            'mole'         => '%s has karma of 6.02214e23 molecules',
            'avogadro'     => '%s has karma of 6.02214e23 molecules',
            'spoon'        => '%s has no karma. There is no spoon',
            'mc^2'         => '%s has karma of E',
            'mc2'          => '%s has karma of E',
            'mcÂ²'          => '%s has karma of E',
            'i'            => '%s haz big karma',
            'karma' => 'The karma law says that all living creatures are responsible for their karma - their actions and the effects of their actions. You should watch yours.'
        );

        $this->karmaBlacklist = array(
            '*',
            'all',
            'everything'
        );

        $this->positiveAnswers = array(
            'No kidding, %owner% totally kicks %owned%\'s ass !',
            'True that.',
            'I concur.',
            'Yay, %owner% ftw !',
            '%owner% is made of WIN!',
            'Nothing can beat %owner%!',
        );

        $this->negativeAnswers = array(
            'No sir, not at all.',
            'You\'re wrong dude, %owner% wins.',
            'I\'d say %owner% is better than %owned%.',
            'You must be joking, %owner% ftw!',
            '%owned% is made of LOSE!',
            '%owned% = Epic Fail',
        );

        // Load or initialize the database
        $class = new ReflectionClass(get_class($this));
        $dir = dirname($class->getFileName() . '/' . $this->name);
        $this->db = new PDO('sqlite:' . $dir . 'karma.db');

        // Check to see if the table exists
        $table = $this->db->query('
            SELECT COUNT(*)
            FROM sqlite_master
            WHERE name = ' . $this->db->quote('karmas')
        )->fetchColumn();

        // Create database tables if necessary
        if (!$table) {
            $this->db->query('
                CREATE TABLE karmas ( word VARCHAR ( 255 ), karma MEDIUMINT );
                CREATE UNIQUE INDEX word ON karmas ( word );
                CREATE INDEX karmaIndex ON karmas ( karma );
                CREATE TABLE comments ( wordid INT , comment VARCHAR ( 255 ) );
                CREATE INDEX wordidIndex ON comments ( wordid );
                CREATE UNIQUE INDEX commentUnique ON comments ( comment );
            ');
        }

        $this->insertKarma = $this->db->prepare('
            INSERT INTO karmas (
                word,
                karma
            )
            VALUES (
                :word,
                :karma
            )
        ');

        $this->insertComment = $this->db->prepare('
            INSERT INTO comments (
                wordid,
                comment
            )
            VALUES (
                :wordid,
                :comment
            )
        ');

        $this->fetchKarma = $this->db->prepare('
            SELECT karma, ROWID id FROM karmas WHERE LOWER(word) = LOWER(:word) LIMIT 1
        ');

        $this->updateKarma = $this->db->prepare('
            UPDATE karmas SET karma = :karma WHERE LOWER(word) = LOWER(:word)
        ');
    }

    /**
     * Checks for dependencies.
     *
     * @return void
     */
    public static function onLoad()
    {
    	if (!extension_loaded('PDO') || !extension_loaded('pdo_sqlite')) {
            $this->fail('PDO and pdo_sqlite extensions must be installed');
    	}
    }

    /**
     * Handles requests for incrementation, decrementation, or lookup of karma
     * ratings sent via messages from users.
     *
     * @return void
     */
    public function onPrivmsg()
    {
        $source = $this->event->getSource();
        $message = $this->event->getArgument(1);
        $target = $this->event->getNick();

        // Command prefix check
        $prefix = preg_quote(trim($this->getConfig('command.prefix')));
        $bot = preg_quote($this->getConfig('connections.nick'));
        $exp = '(?:(?:' . $bot . '\s*[:,>]?\s+(?:' . $prefix . ')?)|(?:' . $prefix . '))';

        // Karma status request
        if (preg_match('#^' . $exp . 'karma\s+(.+)$#i', $message, $m)) {
            // Return user's value if "me" is requested
            if (strtolower($m[1]) === 'me') {
                $m[1] = $target;
            }
            // Clean the term
            $term = $this->doCleanWord($m[1]);

            // Check the blacklist
            if (is_array($this->karmaBlacklist) && in_array($term, $this->karmaBlacklist)) {
                $this->doNotice($target, $term . ' is blacklisted');
                return;
            }

            // Return fixed value if set
            if (isset($this->fixedKarma[$term])) {
                $this->doPrivmsg($source, $target . ': ' . sprintf($this->fixedKarma[$term], $m[1]) . '.');
                return;
            }

            // Return current karma or neutral if not set yet
            $this->fetchKarma->execute(array(':word'=>$term));
            $res = $this->fetchKarma->fetch(PDO::FETCH_ASSOC);

            // Sanity check if someone if someone prefixed their conversation with karma
            if (!$res && substr_count($term, ' ') > 1 && !(substr($m[1], 0, 1) === '(' && substr($m[1], -1) === ')')) {
                return;
            }

            // Clean the raw term if it was contained within brackets
            if (substr($m[1], 0, 1) === '(' && substr($m[1], -1) === ')') {
                $m[1] = substr($m[1], 1, -1);
            }

            if ($res && $res['karma'] != 0) {
                $this->doPrivmsg($source, $target . ': ' . $m[1] . ' has karma of ' . $res['karma'] . '.');
            } else {
                $this->doPrivmsg($source, $target . ': ' . $m[1] . ' has neutral karma.');
            }
        // Incrementation/decrementation request
        } elseif (preg_match('{^' . $exp . '?(?:(\+{2,2}|-{2,2})(\S+?|\(.+?\)+)|(\S+?|\(.+?\)+)(\+{2,2}|-{2,2}))(?:\s+(.*))?$}ix', $message, $m)) {
            if (!empty($m[4])) {
                $m[1] = $m[4]; // Increment/Decrement
                $m[2] = $m[3]; // Word
            }
            $m[3] = (isset($m[5]) ? $m[5] : null); // Comment
            unset($m[4], $m[5]);
            list(, $sign, $word, $comment) = array_pad($m, 4, null);

            // Clean the word
            $word = strtolower($this->doCleanWord($word));
            if (empty($word)) {
                return;
            }

            // Do nothing if the karma is fixed or blacklisted
            if (isset($this->fixedKarma[$word]) ||
                is_array($this->karmaBlacklist) && in_array($word, $this->karmaBlacklist)) {
                return;
            }

            // Force a decrementation if someone tries to update his own karma
            if ($word == strtolower($target) && $sign != '--' && !$this->fromAdmin(true)) {
                $this->doNotice($target, 'Bad ' . $target . '! You can not modify your own Karma. Shame on you!');
                $sign = '--';
            }

            // Antithrottling check
            $host = $this->event->getHost();
            $limit = $this->getConfig('karma.limit');
            // This is waiting on the Acl plugin from Elazar, being bypassed for now
            //if ($limit > 0 && !$this->fromAdmin()) {
            if ($limit > 0) {
                if (isset($this->log[$host][$word]) && $this->log[$host][$word] >= $limit) {
                    // Three strikes, you're out, so lets decrement their karma for spammage
                    if ($this->log[$host][$word] == ($limit+3)) {
                        $this->doNotice($target, 'Bad ' . $target . '! Didn\'t I tell you that you reached your limit already?');
                        $this->log[$host][$word] = $limit;
                        $word = $target;
                        $sign = '--';
                    // Toss a notice to the user if they reached their limit
                    } else {
                        $this->doNotice($target, 'You have currently reached your limit in modifying ' . $word . ' for this day, please wait a bit.');
                        $this->log[$host][$word]++;
                        return;
                    }
                } else {
                    if (isset($this->log[$host][$word])) {
                        $this->log[$host][$word]++;
                    } else {
                        $this->log[$host][$word] = 1;
                    }
                }
            }

            // Get the current value then update or create entry
            $this->fetchKarma->execute(array(':word'=>$word));
            $res = $this->fetchKarma->fetch(PDO::FETCH_ASSOC);
            if ($res) {
                $karma = ($res['karma'] + ($sign == '++' ? 1 : -1));
                $args = array(
                    ':word' => $word,
                    ':karma' => $karma
                );
                $this->updateKarma->execute($args);
            } else {
                $karma = ($sign == '++' ? '1' : '-1');
                $args = array(
                    ':word' => $word,
                    ':karma' => $karma
                );
                $this->insertKarma->execute($args);
                $this->fetchKarma->execute(array(':word'=>$word));
                $res = $this->fetchKarma->fetch(PDO::FETCH_ASSOC);
            }
            $id = $res['id'];
            // Add comment
            $comment = preg_replace('{(?:^//(.*)|^#(.*)|^/\*(.*?)\*/$)}', '$1$2$3', $comment);
            if (!empty($comment)) {
                $this->insertComment->execute(array(':wordid' => $id, ':comment' => $comment));
            }
            // Perform garbage collection on the antithrottling log if needed
            if (date('d') !== $this->lastGc) {
                $this->doGc();
            }
        // Assertion request
        } elseif (preg_match('#^' . $exp . '?([^><]+)(<|>)([^><]+)$#', $message, $m)) {
            // Trim words
            $word1 = strtolower($this->doCleanWord($m[1]));
            $word2 = strtolower($this->doCleanWord($m[3]));
            $operator = $m[2];

            // Do nothing if the karma is fixed
            if (isset($this->fixedKarma[$word1]) || isset($this->fixedKarma[$word2]) ||
                empty($word1) || empty($word2)) {
                return;
            }

            // Fetch first word
            if ($word1 === '*' || $word1 === 'all' || $word1 === 'everything') {
                $res = array('karma' => 0);
                $word1 = 'everything';
            } else {
                $this->fetchKarma->execute(array(':word'=>$word1));
                $res = $this->fetchKarma->fetch(PDO::FETCH_ASSOC);
            }
            // If it exists, fetch second word
            if ($res) {
                if ($word2 === '*' || $word2 === 'all' || $word2 === 'everything') {
                    $res2 = array('karma' => 0);
                    $word2 = 'everything';
                } else {
                    $this->fetchKarma->execute(array(':word'=>$word2));
                    $res2 = $this->fetchKarma->fetch(PDO::FETCH_ASSOC);
                }
                // If it exists, compare and return value
                if ($res2 && $res['karma'] != $res2['karma']) {
                    $assertion = ($operator === '<' && $res['karma'] < $res2['karma']) || ($operator === '>' && $res['karma'] > $res2['karma']);
                    // Switch arguments if they are in the wrong order
                    if ($operator === '<') {
                        $tmp = $word2;
                        $word2 = $word1;
                        $word1 = $tmp;
                    }
                    $this->doPrivmsg($source, $assertion ? $this->fetchPositiveAnswer($word1, $word2) : $this->fetchNegativeAnswer($word1, $word2));
                    // If someone asserts that something is greater or lesser than everything, we increment/decrement that something at the same time
                    if ($word2 === 'everything') {
                        $this->event = clone$this->event;
                        $this->event->setArguments(array($this->event->getArgument(0), '++'.$word1));
                        $this->onPrivmsg();
                    } elseif ($word1 === 'everything') {
                        $this->event = clone$this->event;
                        $this->event->setArguments(array($this->event->getArgument(0), '--'.$word2));
                        $this->onPrivmsg();
                    }
                }
            }
        }
    }

    protected function fetchPositiveAnswer($owner, $owned)
    {
        return str_replace(array('%owner%','%owned%'), array($owner, $owned), $this->positiveAnswers[array_rand($this->positiveAnswers,1)]);
    }

    protected function fetchNegativeAnswer($owned, $owner)
    {
        return str_replace(array('%owner%','%owned%'), array($owner, $owned), $this->negativeAnswers[array_rand($this->negativeAnswers,1)]);
    }

    protected function doCleanWord($word)
    {
        $word = trim($word);
        if (substr($word, 0, 1) === '(' && substr($word, -1) === ')') {
            $word = trim(substr($word, 1, -1));
        }
        $word = preg_replace('#\s+#', ' ', strtolower(trim($word)));
        return $word;
    }

    /**
     * Performs garbage collection on the antithrottling log.
     *
     * @return void
     */
    public function doGc()
    {
        unset($this->log);
        $this->log = array();
        $this->lastGc = date('d');
    }
}
