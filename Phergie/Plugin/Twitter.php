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
 * @package   Phergie_Plugin_Twitter
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2010 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Plugin_Twitter
 */

/**
 * These requires are for library code, so they don't fit Autoload's normal
 * conventions.
 *
 * @link http://github.com/scoates/simpletweet
 */
require dirname(__FILE__) . '/Twitter/twitter.class.php';
require dirname(__FILE__) . '/Twitter/laconica.class.php';

/**
 * Twitter plugin; Allows tweet (if configured) and twitter commands
 *
 * Usage:
 *   twitter username
 *    (fetches and displays the last tweet by @username)
 *   twitter username 3
 *    (fetches and displays the third last tweet by @username)
 *   twitter 1234567
 *    (fetches and displays tweet number 1234567)
 *   http://twitter.com/username/statuses/1234567
 *    (same as `twitter 1234567`)
 *
 * @category Phergie
 * @package  Phergie_Plugin_Twitter
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Plugin_Twitter
 * @uses     Phergie_Plugin_Time pear.phergie.org
 * @uses     Phergie_Plugin_Encoding pear.phergie.org
 */
class Phergie_Plugin_Twitter extends Phergie_Plugin_Abstract
{
    /**
     * Twitter object (from Simpletweet)
     */
    protected $twitter;

    /**
     * Twitter user
     */
    protected $twitteruser = null;

    /**
     * Password
     */
    protected $twitterpassword = null;

    /**
     *	The twitter class as defined by the configuration
     */
    private $_twitterClass;

    /**
     * Register with the URL plugin, if possible
     *
     * @return void
     */
    public function onConnect()
    {
        $plugins = $this->getPluginHandler();
        if ($plugins->hasPlugin('Url')) {
            $plugins->getPlugin('Url')->registerRenderer($this);
        }
    }

    /**
     * Initialize (set up configuration vars)
     *
     * @return void
     */
    public function onLoad()
    {
        if (!isset($this->config['twitter.class'])
            || !$this->_twitterClass = $this->config['twitter.class']
        ) {
            $this->_twitterClass = 'Twitter';
        }

        $this->twitteruser = $this->config['twitter.user'];
        $this->twitterpassword = $this->config['twitter.password'];
        $url = $this->config['twitter.url'];

        $this->setTwitter(
            new $this->_twitterClass(
                $this->twitteruser,
                $this->twitterpassword,
                $url
            )
        );

        $plugins = $this->getPluginHandler();
        $plugins->getPlugin('Encoding');
        $plugins->getPlugin('Time');
    }

    /**
     *	Sets an instance of a twitter
     *	@param  twitter        The twitter instance to set
     *	@return $this          For fluid object handling
     */
    public function setTwitter(Twitter $twitter)
    {
        if (! ($twitter instanceof $this->_twitterClass) )
        {
            require_once 'Phergie/Plugin/Exception.php';
            throw new Phergie_Plugin_Exception(
                sprintf(
                    "%s::%s: Argument twitter `%s' was not an instance of a twitter class.",
                    __CLASS__,
                    __FUNCTION__,
                    get_class($twitter)
                ),
                ERR_FATAL_ERROR
            );
        }
        $this->twitter = $twitter;
    }

    /**
     *	Gets this instance of the twitter plugin
     *	@return  Twitter
     */
    public function getTwitter()
    {
        return $this->twitter;
    }

    /**
     * Fetches the associated tweet and relays it to the channel
     *
     * @param string $tweeter if numeric the tweet number/id, otherwise the
     *  twitter user name (optionally prefixed with @, or a URL to a tweet)
     * @param int    $num     optional tweet number for this user (number of
     *  tweets ago)
     *
     * @return void
     */
    public function onCommandTwitter($tweeter = null, $num = 1)
    {
        $source = $this->getEvent()->getSource();
        $nick = $this->getEvent()->getHostmask()->getNick();
	    if (is_numeric($tweeter)) {
	        $tweet = $this->twitter->getTweetByNum($tweeter);

	    } else if (is_null($tweeter) && $this->twitteruser) {
	        $tweet = $this->twitter->getLastTweet($this->twitteruser, 1);

	    } else if (preg_match('/^https?:\/\/(www\.)?twitter\.com/i', $tweeter)) {
	        if (stripos($tweeter, 'status') !== false) {
                $tweeter = preg_replace('/[^\d]+([\d]+$)/i', '\1', $tweeter);
                $tweet = $this->twitter->getTweetByNum($tweeter);
            } else {
                $twit = explode('/', rtrim($tweeter, '/'));
                $tweeter = array_pop($twit);
                $tweet = $this->twitter->getLastTweet(ltrim($tweeter, '@'), $num);
            }
        } else {
            $tweet = $this->twitter->getLastTweet(ltrim($tweeter, '@'), $num);
	    }

	    if ($tweet) {
	        $this->doPrivmsg($source, $this->formatTweet($tweet));
        } else {
            $this->doPrivmsg($source, "Sorry, $nick I couldn't get that tweet :-(");
        }
    }

    /**
     * Formats a Tweet into a message suitable for output
     *
     * @param object $tweet      JSON-decoded tweet object from Twitter
     * @param bool   $includeUrl whether or not to include the URL in the
     *  formatted output
     *
     * @return string
     */
    protected function formatTweet(StdClass $tweet, $includeUrl = true)
    {
        $ts = $this->plugins->time->getCountDown($tweet->created_at);
        $out =  '<@' . $tweet->user->screen_name .'> '
            . preg_replace('/\s+/', ' ', $tweet->text)
            . ' - ' . $ts . ' ago';
        if ($includeUrl) {
            $out .= ' (' . $this->twitter->getUrlOutputStatus($tweet) . ')';
        }

        $encode = $this->getPluginHandler()->getPlugin('Encoding');

        return $encode->decodeEntities($out);
    }

    /**
     * Renders a URL
     *
     * @param array $parsed parse_url() output for the URL to render
     *
     * @return bool
     */
    public function renderUrl(array $parsed)
    {
        if ($parsed['host'] != 'twitter.com'
            && $parsed['host'] != 'www.twitter.com'
        ) {
            // unable to render non-twitter URLs
            return false;
        }

        $source = $this->getEvent()->getSource();

        if (preg_match('#^/(.*?)/status(es)?/([0-9]+)$#', $parsed['path'], $matches)
        ) {
            $tweet = $this->twitter->getTweetByNum($matches[3]);
            if ($tweet) {
                $this->doPrivmsg($source, $this->formatTweet($tweet, false));
            }
            return true;
        }

        // if we get this far, we haven't satisfied the URL, so bail:
        return false;
    }
}
