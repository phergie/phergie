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
 * @package   Phergie_Plugin_FeedManager
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2011 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Plugin_FeedManager
 */

/**
 * Rss/Atom reader and storage.
 *
 * @category Phergie
 * @package  Phergie_Plugin_FeedManager
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Plugin_FeedManager
 * @uses     Phergie_Plugin_Command pear.phergie.org
 * @uses     Phergie_Plugin_FeedParser pear.phergie.org
 * @uses     Phergie_Plugin_UserInfo pear.phergie.org
 * @uses     Phergie_Plugin_Cron pear.phergie.org
 * @todo     Make Unit tests
 */
class Phergie_Plugin_FeedManager extends Phergie_Plugin_Abstract
{

    /**
     * PDO resource for a SQLite database.
     *
     * @var resource
     */
    protected $db;

    /**
     * Array with registred feeds
     */
    protected $feeds;

    /**
     * Checks for dependencies
     *
     * @return void
     */
    public function onLoad()
    {
        $plugins = $this->getPluginHandler();
        $plugins->getPlugin('Command');

        // Database stuff
        if (!extension_loaded('PDO') || !extension_loaded('pdo_sqlite')) {
            $this->fail('PDO and pdo_sqlite extensions must be installed');
        }

        $defaultDbLocation = dirname(__FILE__) . '/FeedTicker/feedticker.db';

        $fileName = $this->getConfig('feedticker.sqlite_db', $defaultDbLocation);
        $dirName = dirname($fileName);

        $exists = file_exists($fileName);
        if (!file_exists($dirName)) {
            mkdir($dirName);
        }

        if ((file_exists($fileName) && !is_writable($fileName))
            || (!file_exists($fileName) && !is_writable($dirName))
        ) {
            throw new Phergie_Plugin_Exception(
                'SQLite DB file exists and cannot be written,'
                . ' OR does not exist and cannot be created: '
                . $fileName
            );
        }

        try {
            $this->db = new PDO('sqlite:' . $fileName);
        } catch (PDO_Exception $e) {
            throw new Phergie_Plugin_Exception($e->getMessage());
        }
    }

    /**
     * TODO: Function Description
     *
     * @return void
     */
    public function onConnect()
    {
        // Get all feed from database
        $this->feeds = $this->getAllFeeds();
    }

    /**
     * Add a Feed
     *
     * @param String $feed_url ToDo desc
     * @param String $channel  (optional)
     *
     * @return void
     */
    public function onCommandFeedadd($feed_url, $channel='')
    {
        $nick = $this->event->getNick();

        if (empty($channel)) {
            $channel = $this->event->getSource();
        }

        // Check if this feed already exists
        $sql = 'SELECT COUNT(*) FROM ft_feeds WHERE feed_url = '
                . $this->db->quote($feed_url) . ' AND channel = '
                . $this->db->quote($channel);

        if ((bool) $this->db->query($sql)->fetchColumn()) {
            $this->doNotice($nick, 'This feed already exists.');
            return;
        }

        // Get Feed
        if (!$feed = $this->plugins->getPlugin('FeedTicker')->getFeed($feed_url)) {
            $this->doNotice($nick, 'Fail to get data from this feed.');
            return;
        }

        // Parse Feed
        $FeedParser = $this->plugins->getPlugin('FeedParser');
        if ($f = $FeedParser->parseFeed($feed['content'], $feed['header'])) {
            try {

                $defaultDelay = intval(
                    $this->getConfig('FeedTicker.defaultDelay', 300)
                );
                if ($defaultDelay < 60) {
                    $defaultDelay = 60;
                }

                $q = $this->db->prepare(
                    'INSERT INTO ft_feeds (
                        updated, etag, delay, channel, title,
                        description, link, feed_url, active
                    ) VALUES (
                        :updated, :etag, :delay, :channel,
                        :title, :description, :link, :feed_url, :active
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
     * @param Integer $feed_id ToDo desc
     *
     * @return void
     */
    public function onCommandFeeddelete($feed_id)
    {
        $nick = $this->event->getNick();

        if ($this->feedExists($feed_id)) {
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
                $outputTimeFormat = $this->getConfig(
                    'FeedTicker.timeFormat', "Y-m-d H:i"
                );
                $time = date($outputTimeFormat, $f['updated']);
                $txt = sprintf(
                    'ID: %s - %s - %s - %s last update: %s - %s',
                    $f['rowid'], $f['channel'], $f['title'],
                    $f['link'], $time, $active
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
     * @param Integer $feed_id ToDo desc
     * @param Integer $delay   ToDo desc
     *
     * @return void
     */
    public function onCommandFeeddelay($feed_id, $delay)
    {
        $nick = $this->event->getNick();

        $delay = intval($delay);
        if ($delay < 60) {
            $this->doNotice(
                $nick,
                "Less than a minute to check the feed?! Try at least 60 sec."
            );
            return;
        }

        if ($this->feedExists($feed_id)) {
            $q = $this->db->prepare(
                'UPDATE ft_feeds SET delay = :delay WHERE rowid = :rowid'
            );
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
     * @param Integer $feed_id ToDo desc
     *
     * @return void
     */
    public function onCommandFeedenable($feed_id)
    {
        $nick = $this->event->getNick();

        if ($this->feedExists($feed_id)) {
            $q = $this->db->prepare(
                'UPDATE ft_feeds SET active = 1 WHERE rowid = :rowid'
            );
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
     * @param Integer $feed_id ToDo desc
     *
     * @return void
     */
    public function onCommandFeeddisable($feed_id)
    {
        $nick = $this->event->getNick();

        if ($this->feedExists($feed_id)) {
            $q = $this->db->prepare(
                'UPDATE ft_feeds SET active = 0 WHERE rowid = :rowid'
            );
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
     * @param String $feed_id optional
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
            if ($this->feedExists($feed_id)) {
                $q = $this->db->prepare(
                    'DELETE FROM ft_items WHERE feed_id = :feed_id'
                );
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
     * @param String $query ToDo desc
     *
     * @return void
     */
    public function onCommandFeedsearch($query)
    {
        $nick = $this->event->getNick();
        $channel = $this->event->getSource();
        $feeds = $this->getAllFeeds($channel);
        if (empty($feeds)) {
            $this->doNotice($nick, "I found nothing!");
            return;
        }

        $words = explode(" ", trim($query));
        $sql_search = "";
        foreach ($words as $w) {
            $sql_search .= ' AND LOWER(I.title) like LOWER('
                  . $this->db->quote('%'.$w.'%') .
            ')';
        }

        $feed_ids = array();
        foreach ($feeds as $f) { 
            $feed_ids[] = $f['rowid'];
        }
        $feed_ids = implode(',', $feed_ids);

        $sql = 'SELECT I.title, I.link, I.author, I.updated, F.title as source
                FROM ft_items I, ft_feeds F
                WHERE I.feed_id IN ('.$feed_ids.')' . $sql_search . '
                    AND F.rowid = I.feed_id
                ORDER BY I.updated DESC';
        $result = $this->db->query($sql);
        $items = $result->fetchAll();
        $count = count($items);

        if ($count == 0) {
            $this->doNotice($nick, "I found nothing!");
        } else if ($count > 3) {
            $this->doNotice(
                $nick, "I found {$count} items! Try to be more specific."
            );
        } else {
            foreach ($items as $i) {
                $outputFormat = "[%source%] %title% [ %link% ] "
                    . "by %author% at %updated%";
                $outputFormat = $this->getConfig('FeedTicker.format', $outputFormat);
                $outputTimeFormat = $this->getConfig(
                    'FeedTicker.timeFormat', "Y-m-d H:i"
                );
                $updated = date($outputTimeFormat, $i['updated']);
                $txt = str_replace(
                    array(
                        '%source%',
                        '%title%',
                        '%link%',
                        '%author%',
                        '%updated%'
                    ),
                    array(
                        $i['source'],
                        $i['title'],
                        $i['link'],
                        $i['author'],
                        $updated
                    ),
                    $outputFormat
                );
                $this->doPrivmsg($channel, $txt);
            }
        }
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
        $tmpwhere = ' WHERE channel = ' . $this->db->quote($channel);
        $sqlChannel = !empty($channel) ? $tmpwhere : '';
        $sql = 'SELECT rowid, etag, channel, title,
                       link, feed_url, active, delay, updated
                  FROM ft_feeds' . $sqlChannel;
        $result = $this->db->query($sql);
        return $result->fetchAll();
    }

    /**
     * Return the Feedlist
     *
     * @return array
     */
    public function getFeedsList()
    {
        return $this->feeds;
    }

    /**
     * Determines if the feed_id exists
     *
     * @param Integer $feed_id ToDo desc
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
     * Add items on the database
     *
     * @param Integer $feed_id ToDo desc
     * @param Array   $items   ToDo desc
     *
     * @return void
     */
    public function addItems($feed_id, $items)
    {
        if (empty($items)) {
            return;
        }

        $items = array_slice(
            $items, 0, intval($this->getConfig('FeedTicker.itemsLimit', 5))
        );

        $dli = intval($this->getConfig('FeedTicker.dateLimit', 60*60*24*7));
        $dateLimit = time() - $dli;

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

            // Check if this item already exists
            $sql = 'SELECT COUNT(*) FROM ft_items WHERE feed_id = '
                    . $this->db->quote($feed_id) . ' AND link = '
                    . $this->db->quote(trim($i['link']));

            $opa = $this->db->query($sql)->fetchColumn();

            if ((bool) $this->db->query($sql)->fetchColumn()) {
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
}
