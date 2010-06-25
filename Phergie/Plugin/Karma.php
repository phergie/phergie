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
 * Keeps a karma database
 *
 * @category Phergie
 * @package  Phergie_Plugin_Karma
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Plugin_Karma
 * @uses     Phergie_Plugin_Command pear.phergie.org
 * @uses     Phergie_Plugin_Message pear.phergie.org
 */
class Phergie_Plugin_Karma extends Phergie_Plugin_Abstract
{

    /**
     * Stores the SQLite object
     *
     * @var PDO
     */
    protected $db = null;

    /**
     * Retains the last garbage collection date
     *
     * @var string
     */
    protected $lastGc = null;

    /**
     * Logs the karma usages and limits users to one karma change per word
     * and per day
     *
     * @var array
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
     * Message extraction plugin
     *
     * @var Phergie_Plugin_Message
     */
    protected $message;

    /**
     * Check for dependencies.
     *
     * @return void
     */
    public function onLoad()
    {
        $plugins = $this->getPluginHandler();

        $plugins->getPlugin('Command');
        $this->message = $plugins->getPlugin('Message');

    }

    /**
     * Creates the database if it does not already exist.
     *
     * @return void
     */
    public function onConnect()
    {
        $dir = dirname(__FILE__) . '/' . $this->getName();
        $path = $dir . '/karma.db';

        if (!file_exists($dir)) {
            mkdir($dir);
        }

        $this->db = null;
        $this->lastGc = null;
        $this->log = array();

        if(!defined('M_EULER')) {
            define('M_EULER', '0.57721566490153286061');
        }

        // preset variables
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

        // Fixed karma for self
        $static = $this->config['karma.static'];

        if ($static) {
            $this->fixedKarma[strtolower($this->getConnection()->getNick())] = $static;
        }
        try {
            $this->db = new PDO('sqlite:' . $path);
            $this->createTables();
        } catch (PDO_Exception $e) {
            throw new Phergie_Plugin_Exception($e->getMessage());
        }
    }

    /**
     * Intercepts a message and processes any contained recognized commands.
     *
     * @return void
     */
    public function onPrivmsg()
    {
        $event = $this->getEvent();

        $source = $event->getSource();
        $nick = $event->getNick();

        $message = $this->message->getMessage();

        if($message === false) {
            return;
        }

        $modifyPattern = <<<REGEX
		{^
		(?J) # allow overwriting capture names
		\s*  # ignore leading whitespace

		(?:  # start with ++ or -- before the term
			(?P<action> \+\+|--)
			(?:
				(?P<term>\(.+?\)+)(?P<comment>.*) # don't require whitespace after a closing parenthesis
			|
				(?P<term>\S+)(?:\s+(?P<comment>.*))? # do require whitespace after a single word term
			)

		|   # follow the term with ++ or --
			(?P<term>
				\S+?
			|
				\(.+?\)+
			)
			(?P<action>\+\+|--) # allow no whitespace between the term and the action
			(?:\s+(?P<comment>.*))? # and followed by the comment
		)
		$}ix
REGEX;

        $versusPattern = <<<REGEX
        {^
        	(?P<term0>[^><]+)
        		(?P<method><|>)
        	(?P<term1>[^><]+)$#
        $}ix
REGEX;
        $match = null;

        if(preg_match($modifyPattern, $message, $match)) {
            $action = $match['action'];
            $term = $match['term'];
            $comment = strlen($match['comment']) > 0 ? $match['comment'] : null;

            $this->modifyKarma($term, $action, $comment);
        } elseif(preg_match($versusPattern, $message, $match)) {
            $term0 = trim($match['term0']);
            $term1 = trim($match['term1']);
            $method = $match['method'];

            $this->compareKarma($term0, $term1, $method);
        }
    }

    /**
     * Get the karma rating for a given term
     * 
     * @param $term
     */
    public function onCommandKarma($term)
    {
        $source = $this->getEvent()->getSource();
        $nick = $this->getEvent()->getNick();

        // If a karma request is enclosed in parentheses, we assume it is an actual karma request
        $hasParentheses = substr($term, 0, 1) === '(' && substr($term, -1) === ')';

        // Remove parentheses
        if($hasParentheses) {
            $term = trim(substr($term, 1, -1));
        }

        // Lets not investigate empty terms
        if(strlen($term) === 0) {
            return;
        }

        $forSelf = false;

        // By 'me' we mean ourselves
        if(strtolower($term) === 'me') {
            $forSelf = true;
            $term = $nick;
        }

        $canonicalTerm = $this->getCanonicalTerm($term);

        if(in_array($canonicalTerm, $this->karmaBlacklist)) {
            $this->doNotice($nick, "{$term} is blacklisted");
            return;
        }

        if(isset($this->fixedKarma[$canonicalTerm])) {
            $this->doPrivmsg($source, "{$nick}: " . sprintf($this->fixedKarma[$canonicalTerm], $term) . '.');
            return;
        }

        $karma = $this->fetchKarma($term);

        // No karma known for term ...
        if($karma === false) {
            // ... and not targeted, no parentheses and counting more than two words in the sentence
            // probably this is just a sentence starting with "karma", do nothing
            if(!$this->message->isTargetedMessage()
            && !$hasParentheses
            && preg_match_all('|\s+|', $term, $_) > 1
            ) {
                return;
            } else {
                $karma = 0;
            }
        }

        $term = $forSelf ? "you have" : "{$term} has";

        if($karma !== 0) {
            $message = "{$nick}: {$term} karma of {$karma}.";
        } else {
            $message = "{$nick}: {$term} neutral karma.";
        }

        $this->doPrivmsg($source, $message);
    }

    /**
     * Get the canonical form of a given term.
     *
     * In the canonical form all sequences of whitespace are replaced by a single space
     * and all characters are lowercased.
     *
     * @param string $term
     * @return string The canonical term
     */
    protected function getCanonicalTerm($term)
    {
        return strtolower(preg_replace('|\s+|', ' ', $term));
    }

    /**
     * Compare the karma between two terms. Optionally increase/decrease
     * the karma of either term.
     * 
     * @param string $term0
     * @param string $term1
     * @param string $method The comparison method used (either < or >)
     */
    protected function compareKarma($term0, $term1, $method)
    {
        $event = $this->getEvent();
        $nick = $event->getNick();
        $source = $event->getSource();

        $canonicalTerm0 = $this->getCanonicalTerm($term0);

        $canonicalTerm1 = $this->getCanonicalTerm($term1);

        // Nothing to be done
        if(isset($this->fixedKarma[$canonicalTerm0])
                || isset($this->fixedKarma[$canonicalTerm1])
                || $canonicalTerm0 === ''
	            || $canonicalTerm1 === '') {
            return;
        }

        if($canonicalTerm0 === 'me') {
            $term0 = $nick;
            $canonicalTerm0 = $this->getCanonicalTerm($nick);
        }

        if($canonicalTerm1 === 'me') {
            $term1 = $nick;
            $canonicalTerm1 = $this->getCanonicalTerm($nick);
        }

        $everthing = array('all', '*', 'everything');

        if(in_array($canonicalTerm0, $everthing)) {
            $term0 = 'everything';
            $karma0 = 0;
        } else {
            $karma0 = $this->fetchKarma($term0);
        }

        // First word is unknown, do nothing
        if($karma0 === false) {
            return;
        }

        if(in_array($canonicalTerm1, $everthing)) {
            $term1 = 'everything';
            $karma1 = 0;
        } else {
            $karma1 = $this->fetchKarma($term1);
        }

        // Second word is unknown or karmas are equal, do nothing
        if($karma1 === false || $karma0 === $karma1) {
            return;
        }


        $assertion = ($method === '<' && $karma0 < $karma1) || ($method === '>' && $karma0 > $karma1); 
        $replies = $assertion ? $this->positiveAnswers : $this->negativeAnswers;

        if($method === '<') {
            list($term0, $term1) = array($term1, $term0);
        }

        if($term0 === 'everything') {
            $this->modifyKarma($term1, '--', null);
        } elseif($term1 === 'everything') {
            $this->modifyKarma($term0, '++', null);
        }

        if(!$assertion) {
            list($term0, $term1) = array($term1, $term0);
        }

        $message = str_replace(array('%owner%','%owned%'), array($term0, $term1), $replies[array_rand($replies,1)]);

        $this->doPrivmsg($source, $message);


    }

    /**
     * Modify a terms karma.
     * 
     * @param string $term The term to modify
     * @param string $action The karma action (either ++ or --)
     * @param string|null $comment The comment to go with the karma modification
     */
    protected function modifyKarma($term, $action, $comment)
    {
        $event = $this->getEvent();
        $nick = $event->getNick();
        $source = $event->getSource();

        $hasParentheses = substr($term, 0, 1) === '(' && substr($term, -1) === ')';

        // Remove parentheses
        if($hasParentheses) {
            $term = trim(substr($term, 1, -1));
        }

        // Do nothing on a noop
        if(strlen($term) === 0) {
            return;
        }

        if(strtolower($term) === 'me') {
            $forSelf = true;
            $term = $nick;
        }

        $canonicalTerm = $this->getCanonicalTerm($term);

        // Do nothing if the karma is fixed or blacklisted
        if (isset($this->fixedKarma[$canonicalTerm]) || in_array($canonicalTerm, $this->karmaBlacklist)) {
            return;
        }

        if(strcasecmp($term, $nick) === 0 && $action === '++') {
            $this->doNotice($nick, "Bad {$nick}! You can not modify your own Karma. Shame on you!");

            $term = $nick;
            $canonicalTerm = $this->getCanonicalTerm($nick);
            $action = '--';
        }

        $hostMask = $event->getHostmask()->getHost();

        $limit = intval($this->getConfig('karma.limit', 3));

        // Once per day, clear the log
        if($this->lastGc !== date('d')) {
            $this->lastGc = date('d');
            $this->log = array();
        }
            
        // Register the hostmask / term combination
        if(!isset($this->log[$hostMask][$canonicalTerm])) {
            $this->log[$hostMask][$canonicalTerm] = 0;
        }

        if($this->log[$hostMask][$canonicalTerm] >= $limit) {
            $hostLimit = $this->log[$hostMask][$canonicalTerm];

            // Three strikes, you're out, so lets decrement their karma for spammage
            if ($hostLimit == ($limit+3)) {
                $this->doNotice($nick, "Bad {$nick}! Didn't I tell you that you reached your limit already?");
                $this->log[$hostMask][$canonicalTerm] = $limit;

                $term = $nick;
                $canonicalTerm = $this->getCanonicalTerm($nick);
                $action = '--';

            } else {
                $this->doNotice($nick, "You have currently reached your limit in modifying {$term} for this day, please wait a bit.");
                $this->log[$hostMask][$canonicalTerm]++;
                return;
            }
        } else {
            $this->log[$hostMask][$canonicalTerm]++;
        }

        // Insert or update the karma
        $karma = $this->fetchKarma($term);

        $statement = null;

        if ($karma !== false) {
            $karma += $action == '++' ? 1 : -1;

            $statement = $this->db->prepare('
                UPDATE karma SET karma = :karma WHERE term = :term'
            );
        } else {
            $karma = $action == '++' ? 1 : -1;

            $statement = $this->db->prepare('
				INSERT INTO karma ( term, karma )
                	VALUES ( :term, :karma )'
            );
        }

        $args = array(
            ':term'  => $canonicalTerm,
            ':karma' => $karma
        );

        $statement->execute($args);

        // If we have no comment, insert none
        if($comment === null) {
            return;
        }

        $comment = trim($comment);

        $commentPattern = <<<REGEX
        {^
          	(?://|\#)(.*)   # either //<comment> or #<comment>
        |
           	/\*\*?(.*?)\*/ # or /* <comment? */
        $}x
REGEX;
        $comment = trim(preg_replace($commentPattern, '$1$2$3', $comment));

        if($comment === '') {
            return;
        }

        $statement =  $this->db->prepare('
            INSERT INTO comment ( term, comment, sign )
            VALUES ( :term, :comment, :sign )'
        );

        $args = array(':term' => $canonicalTerm, ':comment' => $comment, ':sign' => $action);

        $statement->execute($args);
    }

    /**
     * Get the amount of karma for the specified term
     *
     * @param string $term The term to fetch the karma for
     * @return integer|boolean false if no karma is set, or an integer value denoting the term's karma
     */
    protected function fetchKarma($term)
    {
        $term = $this->getCanonicalTerm($term);

        $query = $this->db->prepare('SELECT karma FROM karma WHERE term = :term LIMIT 1');

        $query->execute(array(':term' => $term));
        $result = $query->fetch(PDO :: FETCH_ASSOC);

        if($result === false) {
            return false;
        }

        return intval($result['karma']);
    }


    /**
     * Determines if a table exists
     *
     * @param string $name Table name
     *
     * @return bool
     */
    protected function hasTable($name)
    {
        $sql = 'SELECT COUNT(*) FROM sqlite_master WHERE name = '
        . $this->db->quote($name);
        return (bool) $this->db->query($sql)->fetchColumn();
    }

    /**
     * Creates the database table(s) (if they don't exist)
     *
     * @return void
     */
    protected function createTables()
    {
        if (!$this->hasTable('karma')) {
            $this->db->exec('
				CREATE TABLE karma ( term VARCHAR ( 255 ), karma MEDIUMINT ) ;
				CREATE UNIQUE INDEX karmaTerm ON karma ( term ) ;
				CREATE INDEX karmaIndex ON karma ( karma ) ;'
            );
            $this->db->exec('
				CREATE TABLE comment ( term VARCHAR ( 255 ) , comment VARCHAR ( 255 ) , sign VARCHAR ( 8 )) ;
				CREATE INDEX commentTerm ON comment ( term ) ;
				CREATE UNIQUE INDEX commentUnique ON comment ( comment ) ;'
            );
        }
    }

}


