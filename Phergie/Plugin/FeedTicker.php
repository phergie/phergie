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
 * @package   Phergie_Plugin_FeedTicker
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2010 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Plugin_FeedTicker
 */

/**
 * Rss/Atom reader and storage.
 *
 * @category Phergie
 * @package  Phergie_Plugin_FeedTicker
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Plugin_FeedTicker
 * @uses     Phergie_Plugin_Command pear.phergie.org
 * @uses     Phergie_Plugin_FeedParser pear.phergie.org
 * @uses     Phergie_Plugin_UserInfo pear.phergie.org
 * @uses     Phergie_Plugin_Cron pear.phergie.org
 * @todo     Remove all debug messages after testing
 */
class Phergie_Plugin_FeedTicker extends Phergie_Plugin_Abstract
{
    /**
     * FeedParser object
     */
    protected $FeedParser;

    /**
     * UserInfo object
     */
    protected $UserInfo;

    /**
     * PDO resource for a SQLite database containing the reminders.
     *
     * @var resource
     */
    protected $db;

    /**
     * Array with registred feeds
     */
    protected $feeds;

    /**
     * Items output format; can use the variables %title%, %link%, %author% and %updated%
     */
    protected $format;

    /**
     * Time format for items output
     */
    protected $timeFormat;

    /**
     * Max number of items to get from the feed
     */
    protected $itemsLimit;

    /**
     * How old an item should be consider as valid?
     */
    protected $dateLimit;

    /**
     * True to makes the plugins works only on active channels
     */
    protected $smartReader;

    /**
     * Max time without actions to set a channel inactive
     */
    protected $idleTime;

    /**
     * Array with channels's last activitie
     */
    protected $channelsStatus = array();

    /**
     * Delay between the delivery of queue items
     */
    protected $showDelayTime;

    /**
     * Number of Items to delivery at same time
     */
    protected $showMaxItems;

    /**
     * Time of last delivery
     */
    protected $lastDeliveryTime;

    /**
     * Checks for dependencies, set default values and starts Cron callback
     *
     * @return void
     */
    public function onLoad()
    {
        $plugins = $this->getPluginHandler();
        $plugins->getPlugin('Command');
        $this->FeedParser = $plugins->getPlugin('FeedParser');
        $this->UserInfo = $plugins->getPlugin('UserInfo');

        // Database stuff
        if (!extension_loaded('PDO') || !extension_loaded('pdo_sqlite')) {
            $this->fail('PDO and pdo_sqlite extensions must be installed');
        }

        $dir = dirname(__FILE__) . '/' . $this->getName();
        $path = $dir . '/feedticker.db';
        if (!file_exists($dir)) {
            mkdir($dir);
        }

        try {
            $this->db = new PDO('sqlite:' . $path);
            $this->createTables();
        } catch (PDO_Exception $e) {
            throw new Phergie_Plugin_Exception($e->getMessage());
        }

        // Set a format to show the items on channel
        $this->format = $this->getConfig('FeedTicker.format', "%title% [ %link% ] by %author% at %updated%");

        // Time format for feed output
        $this->timeFormat = $this->getConfig('FeedTicker.timeFormat', "Y-m-d H:i");

        // Set limits to get items (default: last 5 items and 1 week old)
        $this->itemsLimit = $this->getConfig('FeedTicker.itemsLimit', 5);
        $this->dateLimit = $this->getConfig('FeedTicker.dateLimit', 60*60*24*7);

        // SmartReader: true to get new items only if the channel is active
        $this->smartReader = $this->getConfig('FeedTicker.smartReader', true);

        // idleTime: Max time without actions to set a channel inactive (default 2 hours)
        $this->idleTime = $this->getConfig('FeedTicker.idleTime', 60*60*2);

        // Default 3 minutes and 3 item each delivery
        $this->showDelayTime = $this->getConfig('FeedTicker.showDelayTime', 60*3);
        $this->showMaxItems = $this->getConfig('FeedTicker.showMaxItems', 3);
        $this->lastDeliveryTime = 0;

        // Registering a Cron Callback
        if ($cron = $plugins->getPlugin('Cron')) {
            $cron->registerCallback(array($this, 'feedCheckingCallback'), 60, array(), true);
        }

        // Get all feed from database
        $this->feeds = $this->getAllFeeds();
    }


    /**
     * Cron callback to check the feed
     *
     * @return void
     */
    public function feedCheckingCallback(){
        $now = time();
        $time = $now - $this->idleTime;
        foreach ($this->feeds as $key => $f) {
            echo PHP_EOL . $f['title'] . PHP_EOL;

            // Check just active feeds
            if ($f['active'] == 0) {
                echo 'DEBUG(FeedTicker): Feed disabled...' . PHP_EOL;
                continue;
            }

            // Is time to check this feed again?
            if ($f['updated']+$f['delay'] >= $now) {
                echo 'DEBUG(FeedTicker): Is not time to check this feed yet...' . PHP_EOL;
                continue;
            }

            // Check if bot is on this channel
            if (!isset($this->channelsStatus[$f['channel']])) {
                echo 'DEBUG(FeedTicker): Im not in this channel...' . PHP_EOL;
                continue;
            }

            // Check if this channel is active
            if ($this->channelsStatus[$f['channel']] < $time AND $this->smartReader) {
                echo 'DEBUG(FeedTicker): This channel is inactive...' . PHP_EOL;
                continue;
            }

            $lastUpdate = $f['updated'];

            // Get items
            if ($ret = $this->FeedParser->getFeed($f['feed_url'], $lastUpdate, $f['etag'])) {
               // Set new lastUpdate time and etag for this feed
               $this->setLastUpdate($f['rowid'], $ret->etag, $ret->updated);
               $this->feeds[$key]['etag'] = $ret->etag;
               $this->feeds[$key]['updated'] = $ret->updated;

                // Ignore items if this feed is older than last check
                if (!empty($ret->updated) AND $ret->updated < $lastUpdate) {
                    echo 'DEBUG(FeedTicker): These items are old!' . PHP_EOL;
                    continue;
                }

                // Add new items on database
                $this->addItems($f['rowid'], $ret->items);
            }

        }

        // Check if is time to delivery items
        echo date($this->timeFormat, $this->lastDeliveryTime + $this->showDelayTime) . ' - ' . date($this->timeFormat, time()). PHP_EOL;
        if (($this->lastDeliveryTime + $this->showDelayTime) > time()) {
            echo 'DEBUG(FeedTicker): Is not time to show items yet.' . PHP_EOL;
            return;
        }

        // Check Queue
        foreach ($this->channelsStatus as $channel => $channelTime) {
            if ($channelTime < $time AND $this->smartReader) {
                continue;
            }
            $this->checkQueue($channel);
        }

        $this->lastDeliveryTime = time();
    }

    /**
     * Get unread items from the database and delivery then
     *
     * @param String $channel
     *
     * @return void
     */
    public function checkQueue($channel)
    {

        $items = $this->getUnreadItems($channel);
        if (empty($items)) {
            echo 'DEBUG(FeedTicker): '.$channel.': No items to show.' . PHP_EOL;
            return;
        }

        foreach ($items as $i) {
            $txt = str_replace(
                array('%title%', '%link%', '%author%', '%updated%'),
                array($i['title'], $i['link'], $i['author'], date($this->timeFormat, $i['updated'])),
                $this->format
            );
            $this->doPrivmsg($channel, $txt);
            $this->setItemRead($i['rowid']);
        }
    }

    /**
     * Check if the bot is not alone in this channel and set new channel Status
     *
     * @return void
     */
    public function setChannelStatus($channel)
    {
        $users = $this->UserInfo->getUsers($channel);
        print_r($users);
        if (count($users) > 1) {
            $this->channelsStatus[$channel] = time();
            echo 'DEBUG(FeedTicker): '.$channel.': Is set as active.' . PHP_EOL;
        } else {
            unset($this->channelsStatus[$channel]);
            echo 'DEBUG(FeedTicker): '.$channel.': Is set as inactive.' . PHP_EOL;
        }
    }

    /**
     * Tracks users joining a channel
     *
     * @return void
     */
    public function onJoin()
    {
        $this->setChannelStatus($this->event->getSource());
    }

    /**
     * Tracks users leaving a channel
     *
     * @return void
     */
    public function onPart()
    {
        $this->setChannelStatus($this->event->getSource());
    }

    /**
     * Tracks users quitting a server
     *
     * @return void
     */
    public function onQuit()
    {
        $this->setChannelStatus($this->event->getSource());
    }

    /**
     * Tracks channel chat
     *
     * @return void
     */
    public function onPrivmsg()
    {
        $this->channelsStatus[$this->event->getSource()] = time();
        echo 'DEBUG(FeedTicker): '.$this->event->getSource().': Is set as active.' . PHP_EOL;
    }

    /**
     * Add a Feed
     *
     * @param String $feed_url
     * @param String $channel (optional)
     *
     * @return void
     */
    public function onCommandFeedadd($feed_url, $channel='')
    {
        $source = $this->event->getSource();
        $nick = $this->event->getNick();

        if (empty($channel)) {
            $channel = $source;
        }

        // Check if this
        if ($this->feedAlreadyExists($feed_url, $channel)) {
            $this->doNotice($nick, 'This feed is already registred for this channel.');
            return;
        }

        if ($f = $this->FeedParser->getFeed($feed_url)) {
            try {

                $defaultDelay = intval($this->getConfig('FeedTicker.defaultDelay', 300));
                if ($defaultDelay < 60) {
                    $defaultDelay = 60;
                }

                $q = $this->db->prepare(
                    'INSERT INTO ft_feeds (
                        updated, etag, delay, channel, title, description, link, feed_url, active
                    ) VALUES (
                        :updated, :etag, :delay, :channel, :title, :description, :link, :feed_url, :active
                    )'
                );

                $q->execute(
                    array(
                        ':updated' => $f->updated,
                        ':etag' => $f->etag,
                        ':delay' => $defaultDelay,
                        ':channel' => $channel,
                        ':title' => $f->title,
                        ':description' => $f->description,
                        ':link' => $f->link,
                        ':feed_url' => $feed_url,
                        ':active' => true
                    )
                );
            } catch (PDOException $e) {
                echo 'ERROR(FeedTicker): ' . $e . PHP_EOL;
            }

            if ($rowid = $this->db->lastInsertId()) {
                $this->doNotice($nick, "Done!");
                $this->addItems($rowid, $f->items);
                $this->feeds = $this->getAllFeeds();
            } else {
                $this->doNotice($nick, 'Bad things happened. Feed not saved.');
                return;
            }


        } else {
            $this->doNotice($nick, "This feed is not valid/suported or is empty!");
        }
    }

    /**
     * Removes the Feed from the database
     *
     * @param Integer $feed_id
     *
     * @return void
     */
    public function onCommandFeeddelete($feed_id)
    {
        $nick = $this->event->getNick();

        if ($this->feedExists($feed_id)){
            $q = $this->db->prepare('DELETE FROM ft_feeds WHERE rowid = :rowid');
            $q->execute(array('rowid' => $feed_id));
            $q = $this->db->prepare('DELETE FROM ft_items WHERE feed_id = :feed_id');
            $q->execute(array('feed_id' => $feed_id));
            $this->feeds = $this->getAllFeeds();
            $this->doNotice($nick, "Done!");
        } else {
            $this->doNotice($nick, "This feed doesn't exist!");
        }
    }

    /**
     * Show a list of registred feeds
     *
     * @return void
     */
    public function onCommandFeedlist()
    {
        $nick = $this->event->getNick();

        if (count($this->feeds) > 0) {
            foreach ($this->feeds as $f) {
                $active = $f['active'] ? "Enabled" : "Disabled";
                $time = date($this->timeFormat, $f['updated']);
                $txt = sprintf(
                    'ID: %s - %s - %s - %s last check: %s - %s',
                    $f['rowid'], $f['channel'], $f['title'], $f['link'], $time, $active
                );

                $this->doNotice($nick, $txt);
            }
        } else {
            $this->doNotice($nick, "There's no feed registred!");
        }
    }

    /**
     * Set time delay to read this feed
     *
     * @param Integer $feed_id
     * @param Integer $delay
     *
     * @return void
     */
    public function onCommandFeeddelay($feed_id, $delay)
    {
        $nick = $this->event->getNick();

        $delay = intval($delay);
        if ($delay < 60){
            $this->doNotice($nick, "Less than a minute to check the feed?! Try at least 60 sec.");
            return;
        }

        if ($this->feedExists($feed_id)){
            $q = $this->db->prepare('UPDATE ft_feeds SET delay = :delay WHERE rowid = :rowid');
            $q->execute(array('rowid' => $feed_id, 'delay' => $delay));
            $this->doNotice($nick, "Done!");
        } else {
            $this->doNotice($nick, "This feed doesn't exist!");
        }
        $this->feeds = $this->getAllFeeds();
    }

    /**
     * Enables the Feed
     *
     * @param Integer $feed_id
     *
     * @return void
     */
    public function onCommandFeedenable($feed_id)
    {
        $nick = $this->event->getNick();

        if ($this->feedExists($feed_id)){
            $q = $this->db->prepare('UPDATE ft_feeds SET active = 1 WHERE rowid = :rowid');
            $q->execute(array('rowid' => $feed_id));
            $this->doNotice($nick, "Done!");
        } else {
            $this->doNotice($nick, "This feed doesn't exist!");
        }
        $this->feeds = $this->getAllFeeds();
    }

    /**
     * Disables the Feed
     *
     * @param Integer $feed_id
     *
     * @return void
     */
    public function onCommandFeeddisable($feed_id)
    {
        $nick = $this->event->getNick();

        if ($this->feedExists($feed_id)){
            $q = $this->db->prepare('UPDATE ft_feeds SET active = 0 WHERE rowid = :rowid');
            $q->execute(array('rowid' => $feed_id));
            $this->doNotice($nick, "Done!");
        } else {
            $this->doNotice($nick, "This feed doesn't exist!");
        }
        $this->feeds = $this->getAllFeeds();
    }

    /**
     * Cleans items from the database
     *
     * @param $feed_id (optional)
     *
     * @return void
     */
    public function onCommandFeedclear($feed_id='all')
    {
        $nick = $this->event->getNick();

        if ($feed_id == 'all') {
            $this->db->exec('DELETE FROM TABLE ft_items');
            $this->db->prepare('DELETE FROM ft_items')->execute();
            $this->doNotice($nick, "Done!");
        } else {
            if ($this->feedExists($feed_id)){
                $q = $this->db->prepare('DELETE FROM ft_items WHERE feed_id = :feed_id');
                $q->execute(array('feed_id' => $feed_id));
                $this->doNotice($nick, "Done!");
            } else {
                $this->doNotice($nick, "This feed doesn't exist!");
            }
        }
    }


    /**
     * Search items in the database
     *
     * @param String $query
     *
     * @return void
     */
    public function onCommandFeedsearch($query)
    {
        $nick = $this->event->getNick();
        $channel = $this->event->getSource();
        $feeds = $this->getAllFeeds($channel);
        if (empty($feeds)) {
            return;
        }

        $words = explode(" ", trim($query));
        $sql_search = "";
        foreach ($words as $w) {
            $sql_search .= ' AND LOWER(title) like LOWER('
                  . $this->db->quote('%'.$w.'%') .
            ')';
        }

        $feed_ids = array();
        foreach ($feeds as $f) { $feed_ids[] = $f['rowid']; }
        $feed_ids = implode(',', $feed_ids);

        $sql = 'SELECT title, link, author, updated
                FROM ft_items
                WHERE feed_id IN ('.$feed_ids.')' . $sql_search . '
                ORDER BY updated DESC';
        $result = $this->db->query($sql);
        $items = $result->fetchAll();
        $count = count($items);

        if ($count == 0) {
            $this->doNotice($nick, "I found nothing!");
        } else if ($count > 3) {
            $this->doNotice($nick, "I found {$count} items! Try to be more specific.");
        } else {
            foreach ($items as $i) {
               $txt = str_replace(
                   array('%title%', '%link%', '%author%', '%updated%'),
                   array($i['title'], $i['link'], $i['author'], date($this->timeFormat, $i['updated'])),
                   $this->format
               );
               $this->doPrivmsg($channel, $txt);
            }
        }
    }


    /**
     * Add items on the database
     *
     * @param Integer $feed_id
     * @param Array $items
     *
     * @return void
     */
    public function addItems($feed_id, $items)
    {
        if (empty($items)) {
            return;
        }

        $items = array_slice($items, 0, $this->itemsLimit);
        $dateLimit = time() - $this->dateLimit;

        $q = $this->db->prepare(
            'INSERT INTO ft_items (
                feed_id, updated, title, link, author, read
            ) VALUES (
                :feed_id, :updated, :title, :link, :author, :read
            )'
        );

        foreach ($items as $i) {
            if (!empty($i['updated']) AND $i['updated'] < $dateLimit) {
                continue;
            }

            if ($this->itemAlreadyExists($feed_id, trim($i['link']))) {
                continue;
            }

            $q->execute(
                array(
                    ':feed_id' => $feed_id,
                    ':updated' => trim($i['updated']),
                    ':title' => trim($i['title']),
                    ':link' => trim($i['link']),
                    ':author' => trim($i['author']),
                    ':read' => 0
                )
            );
        }
    }

    /**
     * Check if the Item already exists
     *
     * @param Integer $feed_id
     * @param String $link
     *
     * @return bool
     */
    public function itemAlreadyExists($feed_id, $link)
    {
        $sql = 'SELECT COUNT(*) FROM ft_items WHERE feed_id = '
            . $this->db->quote($feed_id) . ' AND link = '
            . $this->db->quote($link);
        return (bool) $this->db->query($sql)->fetchColumn();
    }

    /**
     * Check if the Feed already exists
     *
     * @param Integer $feed_id
     * @param String $link
     *
     * @return bool
     */
    public function feedAlreadyExists($link, $channel)
    {
        $sql = 'SELECT COUNT(*) FROM ft_feeds WHERE feed_url = '
            . $this->db->quote($link) . ' AND channel = '
            . $this->db->quote($channel);
        return (bool) $this->db->query($sql)->fetchColumn();
    }

    /**
     * Determines if the feed_id exists
     *
     * @param Integer $feed_id
     *
     * @return bool
     */
    public function feedExists($feed_id)
    {
        $sql = 'SELECT COUNT(*) FROM ft_feeds WHERE rowid = '
            . $this->db->quote($feed_id);
        return (bool) $this->db->query($sql)->fetchColumn();
    }

    /**
     * Get all feeds from database
     *
     * @param String $channel (optional)
     *
     * @return array
     */
    public function getAllFeeds($channel='')
    {
        $sqlChannel = !empty($channel) ? ' WHERE channel = ' . $this->db->quote($channel) : '';
        $sql = 'SELECT rowid, etag, channel, title,
                       link, feed_url, active, delay, updated
                  FROM ft_feeds' . $sqlChannel;
        $result = $this->db->query($sql);
        return $result->fetchAll();
    }

    /**
     * Set last update
     *
     * @param Integer $feed_id
     *
     * @return void
     */
    public function setItemRead($item_id)
    {
        $q = $this->db->prepare('UPDATE ft_items SET read = 1 WHERE rowid = :rowid');
        $q->execute(array('rowid' => $item_id));
    }

    /**
     * Get all unread items from this channel
     *
     * @param String $channel
     *
     * @return array
     */
    public function getUnreadItems($channel)
    {
        $feeds = $this->getAllFeeds($channel);
        if (empty($feeds)) {
            return;
        }

        $feed_ids = array();
        foreach ($feeds as $f) { $feed_ids[] = $f['rowid']; }
        $feed_ids = implode(',', $feed_ids);

        $sql = 'SELECT rowid, feed_id, updated, title, link, author
                FROM ft_items WHERE read = 0 AND feed_id IN ('.$feed_ids.')
                ORDER BY updated ASC
                LIMIT '. $this->showMaxItems;
        $result = $this->db->query($sql);
        return $result->fetchAll();
    }

    /**
     * Set last update and last etag received
     *
     * @param Integer $feed_id
     * @param String $etag
     *
     * @return void
     */
    public function setLastUpdate($feed_id, $etag, $updated)
    {
        $q = $this->db->prepare('UPDATE ft_feeds
                                 SET updated = :updated, etag = :etag
                                 WHERE rowid = :rowid');
        $q->execute(array('rowid' => $feed_id, 'updated' => $updated, 'etag' => $etag));
    }

    /**
     * Determines if a table exists
     *
     * @param string $name Table name
     *
     * @return bool
     */
    public function haveTable($name)
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
    public function createTables()
    {
        if (!$this->haveTable('ft_items')) {
            $this->db->exec(
                'CREATE TABLE ft_items (
                        feed_id INTEGER,
                        updated INTEGER,
                        title TEXT,
                        link TEXT,
                        author TEXT,
                        read BOOLEAN
                    )'
            );
        }

        if (!$this->haveTable('ft_feeds')) {
            $this->db->exec(
                'CREATE TABLE ft_feeds (
                        updated INTEGER,
                        etag TEXT,
                        delay INTEGER,
                        channel TEXT,
                        title TEXT,
                        description TEXT,
                        link TEXT,
                        feed_url TEXT,
                        active BOOLEAN
                    )'
            );
        }
    }
}
