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
 * @copyright 2008-2011 Phergie Development Team (http://phergie.org)
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
 * @todo     Make tests with String content
 * @todo     Make Unit tests
 */
class Phergie_Plugin_FeedParser extends Phergie_Plugin_Abstract
{
    /**
     * Feed object
     */
    protected $feed;

    /**
     * Try to discover what kind of feed is it,
     * parse the items and return some informations about it
     *
     * @param Object/String $content Feed body
     * @param Object        $header  Http Header (optional)
     *
     * @return FeedParser
     */
    public function parseFeed($content, $header='')
    {

        if (!$content instanceof SimpleXMLElement) {
            $content = simplexml_load_string($content);
        }

        if (!empty($content)) {
            unset($this->feed);

            if (isset($content->channel)) { // Try to parse RSS 0.91, 0.92 and 2.0
                $this->feed->items = $this->parseItemsRSS($content->channel->item);
                $this->feed->title = (String) $content->channel->title;
                $this->feed->description = (String) $content->channel->description;
                $this->feed->link = (String) $content->channel->link;
                $this->feed->updated = strtotime($content->channel->lastBuildDate);

            } else if (isset($content->entry)) { // Atom 1.0
                // Try to get the source of this feed
                $this->feed->link = null;
                foreach ($content->link as $key => $link) {
                    if ($link->attributes()->rel != 'self') {
                        $this->feed->link = $link->attributes()->href;
                        break;
                    }
                }

                $this->feed->items =    $this->parseItemsAtom($content->entry);
                $this->feed->title =    (String) $content->title;
                $this->feed->updated =  strtotime($content->updated);

            } else { // Unknown format
                echo 'ERROR(Feed): This Feed is not valid or is not supported: '
                    . $url . PHP_EOL;
                return false;
            }

            if (!empty($header)) {
                $this->feed->etag = $header['etag'];
                if (empty($this->feed->updated)) {
                    // Very dificult to happen,
                    // but there are some servers that we can't get any
                    // kind of "last modified time"
                    if (empty($header['last-modified'])) {
                        $this->feed->updated = time();
                    } else {
                        $this->feed->updated = strtotime($header['last-modified']);
                    }
                }
            }

            return $this->feed;
        } else {
            return false;
        }
    }

    /**
     * Items parsing logic for RSS
     *
     * @param string $items TODO description
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

            // Try to get the author and updated time from dc namespace
            // (Used on Wordpress and others)
            $namespaces = $item->getNameSpaces(true);
            $dc = $item->children($namespaces['dc']);

            $author = empty($item->author) ? $dc->creator : $item->author;
            if (empty($autor)) {
                $author = 'Unknown';
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
     * @param string $items TODO desc
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
                $author = 'Unknown';
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
