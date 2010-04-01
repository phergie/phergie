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
 * @package   Phergie_Plugin_AudioScrobbler
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2010 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Plugin_AudioScrobbler
 */

/**
 *
 * @category Phergie
 * @package  Phergie_Plugin_AudioScrobbler
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Plugin_AudioScrobbler
 * @uses     Phergie_Plugin_Command pear.phergie.org
 * @uses     Phergie_Plugin_Http pear.phergie.org
 * @uses     extension simplexml
 */
class Phergie_Plugin_AudioScrobbler extends Phergie_Plugin_Abstract
{
    /**
     * Last.FM API entry point
     *
     * @var string
     */
    protected $lastfmUrl = 'http://ws.audioscrobbler.com/2.0/';
    
    /**
     * Libre.FM API entry point
     *
     * @var string
     */
    protected $librefmUrl = 'http://alpha.dev.libre.fm/2.0/';
    
    /**
     * Scrobbler query string for user.getRecentTracks
     *
     * @var string
     */
    protected $query = '?method=user.getrecenttracks&user=%s&api_key=%s';

    /**
     * HTTP plugin
     *
     * @var Phergie_Plugin_Http
     */
    protected $http;
    
    /**
     * Check for dependencies.
     *
     * @return void
     */
    public function onLoad()
    {
        if (!extension_loaded('simplexml')) {
            $this->fail('SimpleXML php extension is required');
        }
        
        $plugins = $this->getPluginHandler();
        $plugins->getPlugin('Command');
        $this->http = $plugins->getPlugin('Http');
    }
    
    /**
     * Command function to get a user's status on last.fm.
     * 
     * @param string $user User identifier
     *
     * @return void
     */
    public function onCommandLastfm($user = null)
    {
        if ($key = $this->config['audioscrobbler.lastfm_api_key']) {
            $scrobbled = $this->getScrobbled($user, $this->lastfmUrl, $key);
            if ($scrobbled) {
                $this->doPrivmsg($this->getEvent()->getSource(), $scrobbled);
            }
        }
    }

    /**
     * Command function to get a user's status on libre.fm.
     * 
     * @param string $user User identifier
     *
     * @return void
     */
    public function onCommandLibrefm($user = null)
    {
        if ($key = $this->config['audioscrobbler.librefm_api_key']) {
            $scrobbled = $this->getScrobbled($user, $this->librefmUrl, $key);
            if ($scrobbled) {
                $this->doPrivmsg($this->getEvent()->getSource(), $scrobbled);
            }
        }
    }

    /**
     * Simple Scrobbler API function to get a formatted string of the most 
     * recent track played by a user.
     * 
     * @param string $user Username to look up
     * @param string $url  Base URL of the scrobbler service
     * @param string $key  Scrobbler service API key
     *
     * @return string Formatted string of the most recent track played
     */
    public function getScrobbled($user, $url, $key)
    {
        $event = $this->getEvent();
        $user = $user ? $user : $event->getNick();
        $url = sprintf($url . $this->query, urlencode($user), urlencode($key));

        $response = $this->http->get($url);
        if ($response->isError()) {
            $this->doNotice(
                $event->getSource(),
                'Can\'t find status for ' . $user . ': HTTP ' . 
                    $response->getCode() . ' ' . $response->getMessage()
            );
            return false; 
        }
        
        $xml = $response->getContent();
        if ($xml->error) {
            $this->doNotice(
                $event->getSource(),
                'Can\'t find status for ' . $user . ': API ' . $xml->error
            );
            return false; 
        }
        
        $recenttracks = $xml->recenttracks;
        $track = $recenttracks->track[0];
        if (isset($track['nowplaying'])) {
            $msg = sprintf(
                '%s is listening to %s by %s',
                $recenttracks['user'],
                $track->name,
                $track->artist
            );
        } else {
            $msg = sprintf(
                '%s, %s was listening to %s by %s',
                $track->date,
                $recenttracks['user'],
                $track->name,
                $track->artist
            );
        }
        if ($track->streamable == 1) {
            $msg .= ' - ' . $track->url;
        }
        return $msg;
    }
}
