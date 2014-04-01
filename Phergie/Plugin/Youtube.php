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
 * @package   Phergie_Plugin_Youtube
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2012 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Plugin_Youtube
 */

/**
 * Provides commands used to access several services offered by Google
 * including search, translation, weather, maps, and currency and general
 * value unit conversion.
 *
 * @category Phergie
 * @package  Phergie_Plugin_Youtube
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Plugin_Youtube
 * @uses     Phergie_Plugin_Command pear.phergie.org
 * @uses     Phergie_Plugin_Http pear.phergie.org
 */
class Phergie_Plugin_Youtube extends Phergie_Plugin_Abstract
{
    /**
     * Checks for dependencies.
     *
     * @return void
     */
    public function onLoad()
    {
        $plugins = $this->getPluginHandler();
        $plugins->getPlugin('Command');
        $plugins->getPlugin('Http');
        if ($url = $plugins->getPlugin('Url')) {
            $url->registerRenderer($this);
        }
    }

    /**
     * Queries the YouTube video search web service, processes the first
     * result, and sends a message back to the current event source.
     *
     * @param string $query Search term
     *
     * @return object YouTube result object
     */
    protected function queryYoutube($query)
    {
        $url = 'http://gdata.youtube.com/feeds/api/videos';
        $params = array(
            'max-results' => '1',
            'alt' => 'json',
            'q' => $query
        );
        $http = $this->plugins->getPlugin('Http');
        $response = $http->get($url, $params);
        $json = $response->getContent();

        $entries = $json->feed->entry;
        if (!$entries) {
            $this->doNotice($this->event->getNick(), 'Query returned no results');
            return;
        }
        $entry = reset($entries);

        $nick = $this->event->getNick();
        $link = $entry->link[0]->href;
        $title = $entry->title->{'$t'};
        $author = $entry->author[0]->name->{'$t'};
        $seconds = $entry->{'media$group'}->{'yt$duration'}->seconds;
        $published = $entry->published->{'$t'};
        $views = $entry->{'yt$statistics'}->viewCount;
        $rating = $entry->{'gd$rating'}->average;

        $minutes = floor($seconds / 60);
        $seconds = str_pad($seconds % 60, 2, '0', STR_PAD_LEFT);
        $parsed_link = parse_url($link);
        parse_str($parsed_link['query'], $parsed_query);
        $link = 'http://youtu.be/' . $parsed_query['v'];
        $published = date('n/j/y g:i A', strtotime($published));
        $views = number_format($views, 0);
        $rating = round($rating, 2);

        $format = $this->getConfig('youtube.format');
        if (!$format) {
            $format = '%nick%:'
                . ' [ %link% ]'
                . ' "%title%" by %author%,'
                . ' Length %minutes%:%seconds%,'
                . ' Published %published%,'
                . ' Views %views%,'
                . ' Rating %rating%';
        }

        $replacements = array(
            'nick' => $nick,
            'link' => $link,
            'title' => $title,
            'author' => $author,
            'minutes' => $minutes,
            'seconds' => $seconds,
            'published' => $published,
            'views' => $views,
            'rating' => $rating
        );

        $msg = $format;
        foreach ($replacements as $from => $to) {
            $msg = str_replace('%' . $from . '%', $to, $msg);
        }
        $this->doPrivmsg($this->event->getSource(), $msg);
    }

    /**
     * Returns the first result of a YouTube search.
     *
     * @param string $query Search query
     *
     * @return void
     */
    public function onCommandYoutube($query)
    {
        $this->queryYoutube($query);
    }

    /**
     * Renders YouTube URLs.
     *
     * @param array $parsed parse_url() output for the URL to render
     *
     * @return boolean TRUE if the URL was rendered successfully, FALSE
     *         otherwise
     */
    public function renderUrl(array $parsed)
    {
        switch ($parsed['host']) {
        case 'youtu.be':
            $v = ltrim($parsed['path'], '/');
            break;
        case 'youtube.com':
        case 'www.youtube.com':
            parse_str($parsed['query'], $parsed_query);
            if (!empty($parsed_query['v'])) {
                $v = '"' . $parsed_query['v'] . '"';
                break;
            }
        default:
            return false;
        }

        $this->queryYoutube($v);

        return true;
    }
}
