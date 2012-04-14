<?php
/**
 * Phergie
 *
 * PHP version 5
 *
 * LICENSE
 *
 * Sean's Simple Twitter Library
 *
 * Copyright 2008, Sean Coates
 * Usage of the works is permitted provided that this instrument is retained
 * with the works, so that any entity that uses the works is notified of this
 * instrument.
 * DISCLAIMER: THE WORKS ARE WITHOUT WARRANTY.
 * ( Fair License - http://www.opensource.org/licenses/fair.php )
 * Short license: do whatever you like with this.
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.
 * It is also available through the world-wide-web at this URL:
 * http://phergie.org/license
 *
 * @category  Phergie
 * @package   Phergie_Plugin_Twitter
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2012 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Plugin_Twitter
 */

/**
 * Supporting Twitter client library for the Twitter plugin.
 *
 * @category Phergie
 * @package  Phergie_Plugin_Twitter
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Plugin_Twitter
 */
class Twitter
{
    /**
     * Base URL for Twitter API
     *
     * Do not specify user/password in URL
     */
    protected $baseUrl = 'https://twitter.com/';

    /**
     * Full base URL (includes user/pass)
     *
     * (created in Init)
     */
    protected $baseUrlFull = null;

    /**
     * Twitter API user
     */
    protected $user;

    /**
     * Twitter API password
     */
    protected $pass;

    /**
     * Constructor; sets up configuration.
     *
     * @param string $user Twitter user name; null for limited read-only access
     * @param string $pass Twitter password; null for limited read-only access
     */
    public function __construct($user=null, $pass=null)
    {
        $this->baseUrlFull = $this->baseUrl;
        if (null !== $user) {
            // user is defined, so use it in the URL
            $this->user = $user;
            $this->pass = $pass;
            $parsed = parse_url($this->baseUrl);
            $this->baseUrlFull = $parsed['scheme'] . '://' . $this->user . ':'
                    . $this->pass . '@' . $parsed['host'];
            // port (optional)
            if (isset($parsed['port']) && is_numeric($parsed['port'])) {
                $this->baseUrlFull .= ':' . $parsed['port'];
            }
            // append path (default: /)
            if (isset($parsed['path'])) {
                $this->baseUrlFull .= $parsed['path'];
            } else {
                $this->baseUrlFull .= '/';
            }
        }
    }

    /**
     * Fetches a tweet by its number/id
     *
     * @param int $num the tweet id/number
     *
     * @return string (null on failure)
     */
    public function getTweetByNum($num)
    {
        if (!is_numeric($num)) {
            return;
        }
        $tweet = json_decode(file_get_contents($this->getUrlStatus($num)));
        return $tweet;
    }

    /**
     * Reads [last] tweet from user
     *
     * @param string $tweeter the tweeter username
     * @param int    $num     this many tweets ago (1 = current tweet)
     *
     * @return string (false on failure)
     */
    public function getLastTweet($tweeter, $num = 1)
    {
        $source = json_decode(
            file_get_contents($this->getUrlUserTimeline($tweeter))
        );
        if ($num > count($source)) {
            return false;
        }
        $tweet = $source[$num - 1];
        if (!isset($tweet->user->screen_name) || !$tweet->user->screen_name) {
            return false;
        }
        return $tweet;
    }

    /**
     * fetches mentions for a user
     *
     * @param String $sinceId TODO desc
     * @param Int    $count   TODO desc
     *
     * @return TODO desc
     */
    public function getMentions($sinceId=null, $count=20)
    {
        return json_decode(
            file_get_contents(
                $this->getUrlMentions($sinceId, $count)
            )
        );
    }

    /**
     * Fetches followers for a user
     *
     * @param int $cursor TODO desc
     *
     * @return TODO desc
     */
    public function getFollowers($cursor=-1)
    {
        return json_decode(
            file_get_contents(
                $this->getUrlFollowers($cursor)
            )
        );
    }

    /**
     * Follow a userid
     *
     * @param int $userId TODO desc
     *
     * @return TODO desc
     */
    public function follow($userId)
    {
        $params = array(
            'http' => array(
                'method' => 'POST',
                'content' => array(),
                'header' => 'Content-type: application/x-www-form-urlencoded',
            )
        );
        $ctx = stream_context_create($params);
        $fp = fopen($this->getUrlFollow($userId), 'rb', false, $ctx);
        if (!$fp) {
            return false;
        }
        $response = stream_get_contents($fp);
        if ($response === false) {
            return false;
        }
        $response = json_decode($response);
        return $response;
    }

    /**
     * fetches DMs for a user
     *
     * @param String $sinceId TODO desc
     * @param Int    $count   TODO desc
     * @param Int    $page    TODO desc
     *
     * @return TODO desc
     */
    public function getDMs($sinceId=null, $count=20, $page=1)
    {
        return json_decode(
            file_get_contents($this->getUrlDMs($sinceId, $count, $page))
        );
    }

    /**
     * Send DM
     *
     * @param String $screenName TODO Desc
     * @param String $text       TODO Desc
     *
     * @return TODO Desc
     */
    public function sendDM($screenName, $text)
    {
        $data = http_build_query(
            array('screen_name'=>$screenName, 'text'=>$text)
        );
        $params = array(
            'http' => array(
                'method' => 'POST',
                'content' => $data,
                'header' => 'Content-type: application/x-www-form-urlencoded',
            )
        );
        $ctx = stream_context_create($params);
        $fp = fopen($this->getUrlSendDM(), 'rb', false, $ctx);
        if (!$fp) {
            return false;
        }
        $response = stream_get_contents($fp);
        if ($response === false) {
            return false;
        }
        $response = json_decode($response);
        return $response;
    }

    /**
     * Sends a tweet
     *
     * @param string $txt   the tweet text to send
     * @param bool   $limit TODO Desc
     *
     * @return string URL of tweet (or false on failure)
     */
    public function sendTweet($txt, $limit=true)
    {
        if ($limit) {
            $txt = substr($txt, 0, 140); // twitter message size limit
        }
        $data = 'status=' . urlencode($txt);
        $params = array(
            'http' => array(
                'method' => 'POST',
                'content' => $data,
                'header' => 'Content-type: application/x-www-form-urlencoded',
            )
        );
        $ctx = stream_context_create($params);
        $fp = fopen($this->getUrlTweetPost(), 'rb', false, $ctx);
        if (!$fp) {
            return false;
        }
        $response = stream_get_contents($fp);
        if ($response === false) {
            return false;
        }
        $response = json_decode($response);
        return $response;
    }

    /**
     * Returns the base API URL
     *
     * @return TODO desc
     */
    protected function getUrlApi()
    {
        return $this->baseUrlFull;
    }

    /**
     * Returns the status URL
     *
     * @param int $num the tweet number
     *
     * @return TODO desc
     */
    protected function getUrlStatus($num)
    {
        return $this->getUrlApi() . 'statuses/show/'
            . urlencode($num) .'.json';
    }

    /**
     * Returns the user timeline URL
     *
     * @param String $user the username
     *
     * @return TODO desc
     */
    protected function getUrlUserTimeline($user)
    {
        return $this->getUrlApi() . 'statuses/user_timeline/'
                . urlencode($user) . '.json';
    }

    /**
     * Returns the tweet posting URL
     *
     * @return TODO desc
     */
    protected function getUrlTweetPost()
    {
        return $this->getUrlApi() . 'statuses/update.json';
    }

    /**
     * Output URL: status
     *
     * @param Stdclass $tweet TODO desc
     *
     * @return TODO desc
     */
    public function getUrlOutputStatus(StdClass $tweet)
    {
        return $this->baseUrl . '#!/' . urlencode($tweet->user->screen_name)
            . '/statuses/' . urlencode($tweet->id_str);
    }

    /**
     * Return mentions URL
     *
     * @param String $sinceId TODO desc
     * @param Int    $count   TODO desc
     *
     * @return TODO desc
     */
    public function getUrlMentions($sinceId=null, $count=20)
    {
        $url = $this->baseUrlFull . 'statuses/mentions.json?count='
            . urlencode($count);
        if ($sinceId !== null) {
            $url .= '&since_id=' . urlencode($sinceId);
        }
        return $url;
    }

    /**
     * Returns the followers URL
     *
     * @param int $cursor TODO desc
     *
     * @return TODO desc
     */
    public function getUrlFollowers($cursor=-1)
    {
        return $this->baseUrlFull . 'statuses/followers.json?cursor='
            . ((int)$cursor);
    }

    /**
     * Returns the follow-user URL
     *
     * @param int $userid TODO desc
     *
     * @return TODO desc
     */
    public function getUrlFollow($userid)
    {
        return $this->baseUrlFull . 'friendships/create/'
            . ((int) $userid) . '.json';
    }

    /**
     * Returns the get DMs URL
     *
     * @param String $sinceId TODO desc
     * @param Int    $count   TODO desc
     * @param Int    $page    TODO desc
     *
     * @return TODO desc
     */
    public function getUrlDMs($sinceId=null, $count=20, $page=1)
    {
        $url = $this->baseUrlFull . 'direct_messages.json?';
        if ($sinceId !== null) {
            $url .= 'since_id=' . urlencode($sinceId);
        }
        $url .= "&page={$page}";
        $url .= "&count={$count}";
        return $url;
    }

    /**
     * Returns the send DM URL
     *
     * @return TODO Desc
     */
    public function getURLSendDM()
    {
        return $this->baseUrlFull . 'direct_messages/new.json';
    }
}
