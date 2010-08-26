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
 * @package   Phergie_Plugin_FeedParser
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2010 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Plugin_FeedParser
 */

/**
 * Feed parsing logic
 *
 * @category Phergie
 * @package  Phergie_Plugin_FeedParser
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Plugin_FeedParser
 * @uses     Phergie_Plugin_Http pear.phergie.org
 * @todo     Remove all debug messages after testing
 */
class Phergie_Plugin_FeedParser extends Phergie_Plugin_Abstract
{

    /**
     * Feed object
     */
    protected $feed;

    /**
     * Http object
     */
    protected $http;

    /**
     * Checks for dependencies.
     *
     * @return void
     */
    public function onLoad()
    {
        $this->http = $this->getPluginHandler()->getPlugin('Http');
    }

    /**
     * Check if the feed is valid, updated, try to discover what kind of feed is it,
     * parse the items and return some informations about it
     *
     * @param string $url Feed URL
     * @param string $lastCheck Last time this feed was checked
     * @param string $etag Last etag of this feed
     *
     * @return FeedParser
     */
    public function getFeed($url, $updated=0, $etag='')
    {
        echo 'DEBUG(FeedParser): feed: ' . $url . PHP_EOL;
        echo 'DEBUG(FeedParser): updated: ' . date("Y-m-d H:i", $updated) . PHP_EOL;
        echo 'DEBUG(FeedParser): etag: ' . $etag . PHP_EOL . PHP_EOL;

        // If $updated AND $etag are not provide,
        // don't make the head request and avoid an useless request
        if (!empty($updated) OR !empty($etag)) {
            $head = $this->http->head($url);

            if ($head->getCode() == '200') {
                $headers = $head->getHeaders();

                if (!empty($headers['last-modified'])) {
                    $lm = strtotime($headers['last-modified']);
                    if ($lm < $updated) {
                        echo 'DEBUG(FeedParser): OLD! - last-modified - ' . date("Y-m-d H:i", $lm) . PHP_EOL;
                        return false;
                    }
                    echo 'DEBUG(FeedParser): NEW!' . date("Y-m-d H:i", $lm) . PHP_EOL;
                } else if ($etag == $headers['etag']) {
                    echo 'DEBUG(FeedParser): OLD! - etag' . PHP_EOL;
                    return false;
                }
                echo 'DEBUG(FeedParser): NEW!' . $headers['etag'] . PHP_EOL;
            } else {
                echo 'ERROR(Feed): ' . $url . ' - ' .
                    $response->getCode() . ' - ' .
                    $response->getMessage() . PHP_EOL;
                return false;
            }
        }

        // If the feed is updated, request the content
        $response = $this->http->get($url);
        if ($response->getCode() == '200') {
            $content = $response->getContent();
            if (!empty($content)) {
                $headers = $response->getHeaders();

                unset($this->feed);
                $this->feed->etag = empty($headers['etag']) ? $etag : $headers['etag'];

                if (isset($content->channel)) { // Try to parse RSS 0.91, 0.92 and 2.0
                    $this->feed->items =        $this->parseItemsRSS($content->channel->item);
                    $this->feed->title =        (String) $content->channel->title;
                    $this->feed->description =  (String) $content->channel->description;
                    $this->feed->link =         (String) $content->channel->link;
                    $this->feed->updated =      strtotime($content->channel->lastBuildDate);
                    if (empty($this->feed->updated)) {
                        $this->feed->updated = strtotime($headers['last-modified']);
                    }

                    return $this->feed;

                }else if (isset($content->entry)) { // Atom 1.0
                    // Try to get the source of this feed
                    $this->feed->link = NULL;
                    foreach ($content->link as $key => $link) {
                        if ($link->attributes()->rel != 'self') {
                            $this->feed->link = $link->attributes()->href;
                            break;
                        }
                    }

                    $this->feed->items =    $this->parseItemsAtom($content->entry);
                    $this->feed->title =    (String) $content->title;
                    $this->feed->updated =  strtotime($content->updated);
                    if (empty($this->feed->updated)) {
                        $this->feed->updated = strtotime($headers['last-modified']);
                    }
                    echo PHP_EOL.$this->feed->updated.PHP_EOL;

                    return $this->feed;

                } else { // Unknown format
                    echo 'ERROR(Feed): This Feed is not valid or is not supported: ' . $url . PHP_EOL;
                    return false;
                }
            } else {
                echo 'DEBUG(FeedParser): The Feed is empty: ' . $url . PHP_EOL;
                return false;
            }
        } else {
            echo 'ERROR(Feed): ' . $url . ' - ' .
                $response->getCode() . ' - ' .
                $response->getMessage() . PHP_EOL;
            return false;
        }
    }

    /**
     * Items parsing logic for RSS
     *
     * @param string $items
     *
     * @return Array
     */
    public function parseItemsRSS($items)
    {
        $ret = array();
        foreach ($items as $item) {

            if (!empty($item->title)) {
                $title = (String)$item->title;
            } else if (empty($item->title) AND !empty($item->description)) {
                $title = substr(strip_tags($item->description), 0, 100)."...";
            } else {
                // Without a title or description, we dont have an item
                continue;
            }

            //Try to get the author and updated time from dc namespace (Used on Wordpress and others)
            $namespaces = $item->getNameSpaces(true);
            $dc = $item->children($namespaces['dc']);

            $author = empty($item->author) ? $dc->creator : $item->author;
            if (empty($autor)) {
                $autor = 'Unknown';
            }

            $pubDate = empty($item->pubDate) ? $dc->date : $item->pubDate;
            $link = (String) $item->link;

            $ret[] = array(
                'title'     => $title,
                'updated'   => strtotime($pubDate),
                'link'      => $link,
                'author'    => $author
            );

        }
        return $ret;
    }

    /**
     * Items parsing logic for Atom
     *
     * @param string $items
     *
     * @return Array
     */
    public function parseItemsAtom($items)
    {
        $ret = array();
        foreach ($items as $item) {
            $title =    (String) $item->title;
            $link =     (String) $item->link->attributes()->href;
            $pubDate =  (String) strtotime($item->updated);
            $author =   (String) $item->author->name;
            if (empty($autor)) {
                $autor = 'Unknown';
            }

            $ret[] = array(
                'title'     => $title,
                'updated'   => $pubDate,
                'link'      => $link,
                'author'    => $author
            );
        }
        return $ret;
    }
}
