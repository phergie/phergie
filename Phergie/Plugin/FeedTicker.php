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
 * @uses     Phergie_Plugin_Http pear.phergie.org
 * @uses     Phergie_Plugin_Cron pear.phergie.org
 * @uses     Phergie_Plugin_UserInfo pear.phergie.org
 * @uses     Phergie_Plugin_FeedParser pear.phergie.org
 * @uses     Phergie_Plugin_FeedManager pear.phergie.org
 * @todo     Remove all debug messages after testing
 */
class Phergie_Plugin_FeedTicker extends Phergie_Plugin_Abstract
{
    /**
     * PDO resource for a SQLite database.
     *
     * @var resource
     */
    protected $db;

    /**
     * Array with channels's last activitie
     */
    protected $channelsStatus = array();

    /**
     * Time of last delivery
     */
    protected $lastDeliveryTime = 0;

    /**
     * Checks for dependencies, set default values and starts Cron callback
     *
     * @return void
     */
    public function onLoad()
    {
        $this->plugins->getPlugin('FeedManager');
        $this->plugins->getPlugin('FeedParser');
        $this->plugins->getPlugin('Cron');
        $this->plugins->getPlugin('Http');

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

        // Registering a Cron Callback
        $this->plugins->getPlugin('Cron')->registerCallback(
            array($this, 'feedCheckingCallback'),
            60,
            array(),
            true
        );
    }


    /**
     * Cron callback to check the feed
     *
     * @return void
     */
    public function feedCheckingCallback(){
        $now = time();
        $idleTime = intval($this->getConfig('FeedTicker.idleTime', 60*60*2));
        $time = $now - $idleTime;
        $feeds = $this->plugins->getPlugin('FeedManager')->getFeedsList();
        $smartReader = (bool) $this->getConfig('FeedTicker.smartReader', false);

        foreach ($feeds as $key => $f) {
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
            if (!isset($this->channelsStatus[$f['channel']]) AND $smartReader) {
                echo 'DEBUG(FeedTicker): Im not in this channel or it is inactive...' . PHP_EOL;
                continue;
            }

            // Check if this channel is active
            if ($this->channelsStatus[$f['channel']] < $time AND $smartReader) {
                echo 'DEBUG(FeedTicker): This channel is inactive...' . PHP_EOL;
                continue;
            }

            // Get Feed
            if (!$feed = $this->getFeed($f['feed_url'], $f['updated'], $f['etag'])) {
                continue;
            }

            // Parse Feed
            $FeedParser = $this->plugins->getPlugin('FeedParser');
            if ($ret = $FeedParser->parseFeed($feed['content'], $feed['header'])) {

                // Set new lastUpdate time and etag for this feed
                $q = $this->db->prepare('UPDATE ft_feeds
                                         SET updated = :updated, etag = :etag
                                         WHERE rowid = :rowid');
                $q->execute(array('rowid' => $f['rowid'],
                                  'updated' => $ret->updated,
                                  'etag' => $ret->etag));

                // $this->feeds[$key]['etag'] = $ret->etag;
                // $this->feeds[$key]['updated'] = $ret->updated;

                // Ignore items if this feed is older than last check
                if (!empty($ret->updated) AND $ret->updated < $f['updated']) {
                    echo 'DEBUG(FeedTicker): These items are old!' . PHP_EOL;
                    continue;
                }

                // Add new items on database
                $this->plugins->getPlugin('FeedManager')->addItems($f['rowid'], $ret->items);
            }

        }

        // Check if is time to delivery items
        $showDelayTime = intval($this->getConfig('FeedTicker.showDelayTime', 60*3));
        echo date("Y-m-d H:i", $this->lastDeliveryTime + $showDelayTime) . ' - ' . date("Y-m-d H:i", time()). PHP_EOL;
        if (($this->lastDeliveryTime + $showDelayTime) > time()) {
            echo 'DEBUG(FeedTicker): Is not time to show items yet.' . PHP_EOL;
            return;
        }

        // Check Queue
        foreach ($this->channelsStatus as $channel => $channelTime) {
            $smartReader = (bool) $this->getConfig('FeedTicker.smartReader', false);
            if ($channelTime < $time AND $smartReader) {
                continue;
            }
            $this->checkQueue($channel);
        }

        $this->lastDeliveryTime = time();
    }




    /**
     * Check if the feed is valid, updated and returns the content + header
     *
     * @param string $url Feed URL
     * @param string $lastCheck Last time this feed was checked
     * @param string $etag Last etag of this feed
     *
     * @return FeedParser
     */
    public function getFeed($url, $updated=0, $etag='')
    {
        $http = $this->plugins->getPlugin('Http');
        echo 'DEBUG(FeedParser): feed: ' . $url . PHP_EOL;
        echo 'DEBUG(FeedParser): updated: ' . date("Y-m-d H:i", $updated) . PHP_EOL;
        echo 'DEBUG(FeedParser): etag: ' . $etag . PHP_EOL . PHP_EOL;

        // If $updated AND $etag are not provide,
        // don't make the head request and avoid an useless request
        if (!empty($updated) OR !empty($etag)) {
            $response = $http->head($url);

            if ($response->getCode() == '200') {
                $header = $response->getHeaders();

                if (!empty($header['last-modified'])) {
                    $lm = strtotime($header['last-modified']);
                    if ($lm < $updated) {
                        echo 'DEBUG(FeedParser): OLD! - last-modified - ' . date("Y-m-d H:i", $lm) . PHP_EOL;
                        return false;
                    }
                    echo 'DEBUG(FeedParser): NEW!' . date("Y-m-d H:i", $lm) . PHP_EOL;
                } else if ($etag == $header['etag']) {
                    echo 'DEBUG(FeedParser): OLD! - etag' . PHP_EOL;
                    return false;
                }
                echo 'DEBUG(FeedParser): NEW!' . $header['etag'] . PHP_EOL;
            } else {
                echo 'ERROR(Feed): ' . $url . ' - ' .
                    $response->getCode() . ' - ' .
                    $response->getMessage() . PHP_EOL;
                return false;
            }
        }

        // If the feed is updated, request the content
        $response = $http->get($url);
        if ($response->getCode() == '200') {
            return array(
                'content' => $response->getContent(),
                'header' => $response->getHeaders()
            );
        } else {
            echo 'ERROR(Feed): ' . $url . ' - ' .
                $response->getCode() . ' - ' .
                $response->getMessage() . PHP_EOL;
            return false;
        }
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
            $outputFormat = $this->getConfig('FeedTicker.format', "%title% [ %link% ] by %author% at %updated%");
            $outputTimeFormat = $this->getConfig('FeedTicker.timeFormat', "Y-m-d H:i");
            $txt = str_replace(
                array('%title%', '%link%', '%author%', '%updated%'),
                array($i['title'], $i['link'], $i['author'], date($outputTimeFormat, $i['updated'])),
                $outputFormat
            );
            $this->doPrivmsg($channel, $txt);

            // Mark item as read
            $q = $this->db->prepare('UPDATE ft_items SET read = 1 WHERE rowid = :rowid');
            $q->execute(array('rowid' => $i['rowid']));
        }
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
        $feeds = $this->plugins->getPlugin('FeedManager')->getAllFeeds($channel);
        if (empty($feeds)) {
            return;
        }

        $feed_ids = array();
        foreach ($feeds as $f) { $feed_ids[] = $f['rowid']; }
        $feed_ids = implode(',', $feed_ids);

        $showMaxItems = intval($this->getConfig('FeedTicker.showMaxItems', 2));

        $sql = 'SELECT rowid, feed_id, updated, title, link, author
                FROM ft_items WHERE read = 0 AND feed_id IN ('.$feed_ids.')
                ORDER BY updated ASC
                LIMIT '. $showMaxItems;
        $result = $this->db->query($sql);
        return $result->fetchAll();
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

    /**
     * Check if the bot is not alone in this channel and set new channel Status
     *
     * @return void
     */
    public function setChannelStatus($channel)
    {
        if ((bool) $this->getConfig('FeedTicker.smartReader', false)){
            $this->plugins->getPlugin('UserInfo');
            $users = $this->plugins->getPlugin('UserInfo')->getUsers($channel);
            if (count($users) > 1) {
                $this->channelsStatus[$channel] = time();
                echo 'DEBUG(FeedTicker): '.$channel.': Is set as active.' . PHP_EOL;
            } else {
                unset($this->channelsStatus[$channel]);
                echo 'DEBUG(FeedTicker): '.$channel.': Is set as inactive.' . PHP_EOL;
            }
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
}
