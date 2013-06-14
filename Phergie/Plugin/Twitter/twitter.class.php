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

require_once dirname(__FILE__) . '/twitteroauth/twitteroauth.php';

/**
 * Supporting Twitter client library for the Twitter plugin. Utilizes Abraham Williams's PHP TwitterOAuth Library, with namespaces stripped
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
     * @var TwitterOAuth
     */
    protected $api;

    /**
     * Did the credentials pass a verification check?
     *
     * @var bool
     */
    protected $authenticatedAsUser = false;

    /**
     * UID of authenticated user (gleaned from OAuth Token and OAuth Token Secret)
     *
     * @var string
     */
    protected $authenticatedUID;

    /**
     * @param string $consumerkey Consumer Key provided by Twitter for your application in the developer portal
     * @param string $consumersecret Consumer Secret provided by Twitter for your application in the developer portal
     * @param null|string $oauthtoken OAuth Token for specific user received during authentication steps or via developer portal
     * @param null|string $oauthtokensecret OAuth Token Secret for specific user received during authenitcation steps or via developer portal
     */
    public function __construct($consumerkey, $consumersecret, $oauthtoken, $oauthtokensecret)
    {
        $this->api = new TwitterOAuth($consumerkey, $consumersecret, $oauthtoken, $oauthtokensecret);

        if ($oauthtoken && $oauthtokensecret) {
            $resp = $this->api->get('account/verify_credentials', array('skip_status' => true));
            //If OAuth Token is valid, previous response will return 200, otherwise 401
            if (200 == $this->api->lastStatusCode()) {
                $this->authenticatedAsUser = true;
                $this->authenticatedUID = $resp->id_str;
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
        
        $resp = $this->api->get('statuses/show/' . urlencode($num));

        if (200 != $this->api->lastStatusCode())
            return false;

        return $resp;
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
        $source = $this->api->get('statuses/user_timeline', array('screen_name' => $tweeter, 'count' => $num));

        if (200 != $this->api->lastStatusCode()) {
            var_dump($this->api->lastStatusCode(), $source);
            return false;
        }

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
        if (!$this->authenticatedAsUser)
            return false;

        $opts = array(
            'count' => $count,
        );

        if ($sinceId)
            $opts['since_id'] = $sinceId;

        $resp = $this->api->get('statuses/mentions_timeline', $opts);

        if (200 != $this->api->lastStatusCode())
            return false;

        return $resp;
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
        if (!$this->authenticatedAsUser)
            return false;

        $resp = $this->api->get('followers/ids', array('user_id' => $this->authenticatedUID, 'cursor' => $cursor));

        if (200 != $this->api->lastStatusCode())
            return false;

        return $resp;
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
        if (!$this->authenticatedAsUser)
            return false;

        $resp = $this->api->post('friendships/create', array('user_id' => $userId));

        if (200 != $this->api->lastStatusCode())
            return false;

        return $resp;
    }

    /**
     * fetches DMs for a user
     *
     * @param String $sinceId TODO desc
     * @param Int    $count   TODO desc
     * @param Int    $page    DEPRECATED
     *
     * @return TODO desc
     */
    public function getDMs($sinceId=null, $count=20, $page=1)
    {
        if (!$this->authenticatedAsUser)
            return false;

        $opts = array(
            'count' => $count,
        );

        if ($sinceId)
            $opts['since_id'] = $sinceId;

        $resp = $this->api->get('direct_messages', $opts);

        if (200 != $this->api->lastStatusCode())
            return false;

        return $resp;
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
        if (!$this->authenticatedAsUser)
            return false;

        $opts = array(
            'screen_name' => $screenName,
            'text' => $text,
        );

        $resp = $this->api->post('direct_messages/new', $opts);

        if (200 != $this->api->lastStatusCode())
            return false;

        return $resp;
    }

    /**
     * Sends a tweet
     *
     * @param string $txt   the tweet text to send
     * @param bool   $limit DEPRECATED
     *
     * @return string URL of tweet (or false on failure)
     */
    public function sendTweet($txt, $limit=false)
    {
        if (!$this->authenticatedAsUser)
            return false;

        $resp = $this->api->post('statuses/update', array('status' => $txt));

        if (200 != $this->api->lastStatusCode())
            return false;

        return $resp;
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
        return 'https://twitter.com/'. urlencode($tweet->user->screen_name)
        . '/statuses/' . urlencode($tweet->id_str);
    }
}
