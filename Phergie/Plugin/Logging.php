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
 * @package   Phergie_Plugin_Logging
 * @author    Eli White <eli@eliw.com>
 * @copyright 2008-2012 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 */

/**
 * Based upon configuration provided, log all messages that come across the channel.
 *
 * CONFIGURATION:
 *   The following configuration items are required, only 'retry' is optional:
 *       'logging.dsn' = A PDO DSN string to connect to your database
 *       'logging.user' = Username for your database access
 *       'logging.pass' = Password for the same:
 *       'logging.table' = Table name to store data in
 *
 *       'logging.dsn' => 'mysql:host=127.0.0.1;port=3306;dbname=test;charset=utf8',
 *       'logging.user' => 'MyUser',
 *       'logging.pass' => 'UserPassword',
 *       'logging.table' => 'phergie',
 *
 *
 * DATA SCHEMA:
 *   You will need to have created an appropriate database table to store these log entries.
 *   At a minimum it needs to include host, channel, type, nick, and message fields.  Beyond that
 *   you can get creative.  The following is an example table structure you might use:
 *
 *      CREATE TABLE `phergie_log` (
 *           `id`                int(11) unsigned NOT NULL AUTO_INCREMENT,
 *           `host`              varchar(265) NOT NULL,
 *           `channel`           varchar(50) NOT NULL,
 *           `type`              varchar(10) NOT NULL,
 *           `nick`              varchar(50) NOT NULL,
 *           `message`           varchar(1024) NULL,
 *           `created_on`        timestamp DEFAULT current_timestamp NOT NULL,
 *           PRIMARY KEY (`id`),
 *           INDEX `idx_phergie_chan` (`channel`)
 *       ) ENGINE = innodb DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;
 *       
 *  
 *
 * @category Phergie
 * @package  Phergie_Plugin_Logging
 * @author   Eli White <eli@eliw.com>
 * @license  http://phergie.org/license New BSD License
 */
class Phergie_Plugin_Logging extends Phergie_Plugin_Abstract
{
    /**
     * Database connection
     *
     * @var PDO
     */
    protected $db;

    /**
     * Remembers if we've failed our database connection and given up.
     *
     * @var mixed (boolean or timestamp)
     */
    protected $failed = FALSE;
    
    /**
     * onLoad
     *
     * Prepare for logging
     *
     * @author Eli White <eli@eliw.com>
     * @return void
     */
    public function onLoad()
    {
        // Attempt to connect to the database:
        if (!$this->_connectDB()) {
            $this->fail("Unable to connect to logging database");
        }
    }
    
    /**
     * _connectDB
     *
     * Attempts to connect to the database.
     *
     * @author Eli White <eli@eliw.com>
     * @return boolean
     */
    private function _connectDB()
    {
        // Read in the configuration:
        $dsn = $this->getConfig('logging.dsn');
        $user = $this->getConfig('logging.user');
        $pass = $this->getConfig('logging.pass');
        $table = $this->getConfig('logging.table');
        
        // Bail if we don't have dsn & table:
        if (!$dsn || !$table) {
            $this->fail("logging.dsn and logging.table must be configured");
        }
        
        // Connect to database:
        try {
            $this->db = new PDO($dsn, $user, $pass);
            return true;
        } catch (PDOException $pe) {
            return false;
        }
    }

    /**
     * _log
     *
     * Actual workhorse method that does the logging.
     *
     * @param Phergie_Event_Request $event The event we are operating on
     * @param string $nick Who did this?
     * @param string $message What was said?
     * @author Eli White <eli@eliw.com>
     * @return boolean
     */
    private function _log(Phergie_Event_Request $event, $nick, $message = NULL)
    {
        // Read in a few pieces of data from the connection
        $connection = $this->getConnection();
        $host = $connection->getHost() .':'. $connection->getPort();

        // Now from the event
        $type = $event->getType();
        $channel = $event->getSource();
        
        // Attempt to write to the database
        $table = $this->getConfig('logging.table');
        $sql = "INSERT INTO `{$table}` (`host`, `channel`, `type`, `nick`, `message`) 
                VALUES (?, ?, ?, ?, ?)";
        $success = false;
        if ($this->db) {
            if ($stmt = $this->db->prepare($sql)) {
                $success = $stmt->execute(array($host, $channel, $type, $nick, $message));
            }
        }

        return $success;
    }

    /**
     * log
     *
     * Wrapper method on the logging that adds in additional features, such as error handling
     *  and retrying the long living database connection in case it dropped.
     *
     * @param Phergie_Event_Request $event The event we are operating on
     * @param string $nick Who did this?
     * @param string $message What was said?
     * @author Eli White <eli@eliw.com>
     * @return void
     */
    protected function log(Phergie_Event_Request $event, $nick, $message = NULL)
    {
        // Because of repeated logic, this is a 'two loop max loop'
        $attempts = 1; // Number of times we'll retry a failed connection here.
        $immediate = false;
        while ($attempts > 0) {
            // If we start off failed, go ahead and try to connect:
            if ($this->failed) {
                $attempts--;
                $retry = $this->getConfig('logging.retry', 300);
                if ($immediate || (($retry + $this->failed) < time())) {
                    if ($this->failed = ($this->_connectDB() ? false : time())) {
                        $this->doPrivmsg($channel,
                            "WARNING: DB connection for Logging Plugin has failed.  ".
                            "Retrying in {$retry} seconds");
                    } else {
                        $this->doPrivmsg($channel,
                          "NOTICE: DB connection for Logging Plugin reestablished.");
                    }
                }
            }
            
            // Now if we aren't failed, attempt it, break the loop if it works, else repeat
            if (!$this->failed) {
                if ($this->_log($event, $nick, $message)) {
                    break;
                } else {
                    $this->failed = $immediate = true;
                }
            }
        }
    }

    /**
     * onPrivmsg
     *
     * Watches for & logs any incoming messages from the channel
     *
     * @author Eli White <eli@eliw.com>
     * @return void
     */
    public function onPrivmsg()
    {
        // Figure out our data
        $event = $this->getEvent();
        if ($event->isInChannel()) {
            $this->log($event, $event->getNick(), $event->getText());
        }
    }

    /**
     * onJoin
     *
     * Handler for when a user joins a channel.
     *
     * @author Eli White <eli@eliw.com>
     * @return void
     */
    public function onJoin()
    {
        // Push it to the log
        $event = $this->getEvent();
        $this->log($event, $event->getNick());
    }

    /**
     * onPart
     *
     * Handler for when a user leaves a channel.
     *
     * @author Eli White <eli@eliw.com>
     * @return void
     */
    public function onPart()
    {
        // Push it to the log
        $event = $this->getEvent();
        $this->log($event, $event->getNick());
    }

    /**
     * onAction
     *
     * Handler for when the bot receives a CTCP ACTION request.
     *
     * @author Eli White <eli@eliw.com>
     * @return void
     */
    public function onAction()
    {
        // Push it to the log
        $event = $this->getEvent();
        $this->log($event, $event->getNick(), $event->getText());
    }

    /**
     * onNick
     *
     * Handler for when someone changes their nickname.
     *
     * @author Eli White <eli@eliw.com>
     * @return void
     */
    public function onNick()
    {
        // Push it to the log
        $event = $this->getEvent();
        $this->log($event, $event->getNick(), $event->getNickname());
    }

    /**
     * preDispatch
     *
     * Processes events before they are dispatched and logs appropriate ones.
     *
     * @author Eli White <eli@eliw.com>
     * @return void
     */
    public function preDispatch() {
        $events = $this->events->getEvents();
        foreach ($events as $event) {
            switch ($type = $event->getType()) {
            case Phergie_Event_Request::TYPE_PRIVMSG:
            case Phergie_Event_Request::TYPE_ACTION:
                $this->log($event, $this->getConnection()->getNick(), $event->getArgument(1));
                break;
            }
        }
    }
}
