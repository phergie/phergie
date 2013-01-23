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
 * @package   Phergie_Plugin_Remind
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2012 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Plugin_Remind
 */

/**
 * Parses and logs messages that should be relayed to other users the next time
 * the recipient is active on the same channel.
 *
 * @category Phergie
 * @package  Phergie_Plugin_Remind
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Plugin_Remind
 * @uses     Phergie_Plugin_Command pear.phergie.org
 * @uses     Phergie_Plugin_Time pear.phergie.org
 * @uses     extension PDO
 * @uses     extension pdo_sqlite
 */
class Phergie_Plugin_Remind extends Phergie_Plugin_Abstract
{
    /**
     * Number of reminders to show in public.
     */
    protected $publicReminders = 3;

    /**
     * Send reminders when a user joins the channel or not.
     *
     * @var bool
     */
    protected $remindOnJoin = false;

    /**
     * Respond *only* to targeted reminders or not.
     *
     * @var bool
     */
    protected $onlyTargetedReminders = false;

    /**
     * PDO resource for a SQLite database containing the reminders.
     *
     * @var resource
     */
    protected $db;

    /**
     * Flag that indicates whether or not to use an in-memory reminder list.
     *
     * @var bool
     */
    protected $keepListInMemory = true;

    /**
     * In-memory store for pending reminders.
     */
    protected $msgStorage = array();

    /**
     * Check for dependencies.
     *
     * @return void
     */
    public function onLoad()
    {
        $plugins = $this->getPluginHandler();
        $plugins->getPlugin('Command');
        $plugins->getPlugin('Time');

        if (!extension_loaded('PDO') || !extension_loaded('pdo_sqlite')) {
            $this->fail('PDO and pdo_sqlite extensions must be installed');
        }

        $dir = dirname(__FILE__) . '/' . $this->getName();
        $path = $dir . '/reminder.db';
        if (!file_exists($dir)) {
            mkdir($dir);
        }

        if (isset($this->config['remind.use_memory'])) {
            $this->keepListInMemory = (bool) $this->config['remind.use_memory'];
        }

        if (isset($this->config['remind.public_reminders'])) {
            $this->publicReminders = (int) $this->config['remind.public_reminders'];
            $this->publicReminders = max($this->publicReminders, 0);
        }

        if (isset($this->config['remind.remind_on_join'])) {
            $this->remindOnJoin = (bool) $this->config['remind.remind_on_join'];
        }

        if (isset($this->config['remind.only_targeted_reminders'])) {
            $this->onlyTargetedReminders = (bool) $this->config['remind.only_targeted_reminders'];
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
        $this->handleDelivery();
    }

    /**
     * Handler for when a user joins a channel.
     *
     * @return void
     */
    public function onJoin()
    {
        if ($this->remindOnJoin) {
            $this->handleDelivery();
        }
    }

    /**
     * Deliver reminders to a user.
     *
     * @return void
     */
    protected function handleDelivery()
    {
        $source = $this->getEvent()->getSource();
        $nick = $this->getEvent()->getNick();

        $this->deliverReminders($source, $nick);
    }

    /**
     * Handle reminder requests
     *
     * @param string $recipient recipient of the message
     * @param string $message   message to tell the recipient
     *
     * @return void
     * @see handleRemind()
     */
    public function onCommandTell($recipient, $message)
    {
        $this->handleRemind($recipient, $message);
    }

    /**
     * Handle reminder requests
     *
     * @param string $recipient recipient of the message
     * @param string $message   message to tell the recipient
     *
     * @return void
     * @see handleRemind()
     */
    public function onCommandAsk($recipient, $message)
    {
        $this->handleRemind($recipient, $message);
    }

    /**
     * Handle reminder requests
     *
     * @param string $recipient recipient of the message
     * @param string $message   message to tell the recipient
     *
     * @return void
     * @see handleRemind()
     */
    public function onCommandRemind($recipient, $message)
    {
        $this->handleRemind($recipient, $message);
    }

    /**
     * Handles the tell/remind command (stores the message)
     *
     * @param string $recipient name of the recipient
     * @param string $message   message to store
     *
     * @return void
     */
    protected function handleRemind($recipient, $message)
    {
        // Don't do anything if we are only responding to targeted reminders and this isn't a targeted message.
        if ($this->onlyTargetedReminders && ! $this->plugins->message->isTargetedMessage()) {
            return;
        }

        $source = $this->getEvent()->getSource();
        $nick = $this->getEvent()->getNick();

        $myself = $this->getConnection()->getNick();
        if ($myself == $recipient) {
            $this->doPrivmsg($source, 'You can\'t send reminders to me.');
            return;
        }

        if (!$this->getEvent()->isInChannel()) {
            $this->doPrivmsg($source, 'Reminders must be requested in-channel.');
            return;
        }

        $q = $this->db->prepare(
            'INSERT INTO remind
                (
                    time,
                    channel,
                    recipient,
                    sender,
                    message
                )
            VALUES
                (
                    :time,
                    :channel,
                    :recipient,
                    :sender,
                    :message
               )'
        );
        try {
            $q->execute(
                array(
                    'time' => date(DATE_RFC822),
                    'channel' => $source,
                    'recipient' => strtolower($recipient),
                    'sender' => strtolower($nick),
                    'message' => $message
                )
            );
        } catch (PDOException $e) {
        }

        if ($rowid = $this->db->lastInsertId()) {
            $this->doPrivmsg($source, 'ok, ' . $nick . ', message stored');
        } else {
            $this->doPrivmsg(
                $source,
                $nick . ': bad things happened. Message not saved.'
            );
            return;
        }

        if ($this->keepListInMemory) {
            $this->msgStorage[$source][strtolower($recipient)] = $rowid;
        }
    }

    /**
     * Determines if the user has pending reminders, and if so, delivers them.
     *
     * @param string $channel channel to check
     * @param string $nick    nick to check
     *
     * @return void
     */
    protected function deliverReminders($channel, $nick)
    {
        if (!$this->getEvent()->isInChannel()) {
            // private message, not a channel, so don't check
            return;
        }

        // short circuit if there's no message in memory (if allowed)
        if ($this->keepListInMemory
            && !isset($this->msgStorage[$channel][strtolower($nick)])
        ) {
            return;
        }

        // fetch and deliver messages
        $reminders = $this->fetchMessages($channel, $nick);
        if (count($reminders) > $this->publicReminders) {
            $msgs = array_slice($reminders, 0, $this->publicReminders);
            $privmsgs = array_slice($reminders, $this->publicReminders);
        } else {
            $msgs = $reminders;
            $privmsgs = false;
        }

        foreach ($msgs as $msg) {
            $ts = $this->plugins->time->getCountdown($msg['time']);
            $formatted = sprintf(
                '%s: (from %s, %s ago) %s',
                $nick, $msg['sender'], $ts, $msg['message']
            );
            $this->doPrivmsg($channel, $formatted);
            $this->deleteMessage($msg['rowid'], $channel, $nick);
        }

        if ($privmsgs) {
            foreach ($privmsgs as $msg) {
                $ts = $this->plugins->time->getCountdown($msg['time']);
                $formatted = sprintf(
                    'from %s, %s ago: %s',
                    $msg['sender'], $ts, $msg['message']
                );
                $this->doPrivmsg($nick, $formatted);
                $this->deleteMessage($msg['rowid'], $channel, $nick);
            }
            $formatted = sprintf(
                '%s: (%d more messages sent in private.)',
                $nick, count($privmsgs)
            );
            $this->doPrivmsg($channel, $formatted);
        }
    }

    /**
     * Get pending messages (for a specific channel/recipient)
     *
     * @param string $channel   channel on which to check for pending messages
     * @param string $recipient user for which to check pending messages
     *
     * @return array of records
     */
    protected function fetchMessages($channel = null, $recipient = null)
    {
        if ($channel) {
            $qClause = 'WHERE channel = :channel AND recipient LIKE :recipient';
            $params = compact('channel', 'recipient');
        } else {
            $qClause = '';
            $params = array();
        }
        $q = $this->db->prepare(
            'SELECT rowid, channel, sender, recipient, time, message
            FROM remind ' . $qClause
        );
        $q->execute($params);
        return $q->fetchAll();
    }

    /**
     * Deletes a delivered message
     *
     * @param int    $rowid   ID of the message to delete
     * @param string $channel message's channel
     * @param string $nick    message's recipient
     *
     * @return void
     */
    protected function deleteMessage($rowid, $channel, $nick)
    {
        $nick = strtolower($nick);
        $q = $this->db->prepare('DELETE FROM remind WHERE rowid = :rowid');
        $q->execute(array('rowid' => $rowid));

        if ($this->keepListInMemory) {
            if (isset($this->msgStorage[$channel][$nick])
                && $this->msgStorage[$channel][$nick] == $rowid
            ) {
                unset($this->msgStorage[$channel][$nick]);
            }
        }
    }

    /**
     * Determines if a table exists
     *
     * @param string $name Table name
     *
     * @return bool
     */
    protected function haveTable($name)
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
        if (!$this->haveTable('remind')) {
            $this->db->exec(
                'CREATE TABLE
                    remind
                    (
                        time INTEGER,
                        channel TEXT,
                        recipient TEXT,
                        sender TEXT,
                        message TEXT
                    )'
            );
        }
    }

    /**
     * Populates the in-memory cache of pending reminders
     *
     * @return void
     */
    protected function populateMemory()
    {
        if (!$this->keepListInMemory) {
            return;
        }
        foreach ($this->fetchMessages() as $msg) {
            $this->msgStorage[$msg['channel']][$msg['recipient']] = $msg['rowid'];
        }
    }
}
