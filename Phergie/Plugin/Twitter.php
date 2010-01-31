<?php

// weak... TODO: autoload
require dirname(__FILE__) . '/Twitter/twitter.class.php';
require dirname(__FILE__) . '/Twitter/laconica.class.php';

/**
 * Twitter plugin. Allows tweet (if configured) and twitter commands
 */
class Phergie_Plugin_Twitter extends Phergie_Plugin_Abstract
{
    /**
     * Twitter object
     */
    protected $_twitter;

    /**
     * Twitter user
     */
    protected $_twitteruser = null;

    /**
     * Password
     */
    protected $_twitterpassword = null;

    /**
     * Allow only admins to tweet
     */
    static public $TWEET_REQUIRE_ADMIN = true;

	/**
	 * Register with the URL plugin, if possible
	 */
	public function onConnect()
	{
		if ($url = $this->getPluginHandler()->getPlugin('Url')) {
			$url->registerRenderer($this);
		}
	}

    /**
     * Initialize (set up configuration vars)
     *
     * @return void
     */
    public function onLoad()
    {
        // see if tweetrequireadmin defined in config
        if (
            isset($this->_config['twitter.tweetrequireadmin']) &&
            $req = $this->_config['twitter.tweetrequireadmin']
        ) {
            // if so, override default
            self::$TWEET_REQUIRE_ADMIN = $req;
        }
        if (
            !isset($this->_config['twitter.class']) ||
            !$twitterClass = $this->_config['twitter.class']
        ) {
            $twitterClass = 'Twitter';
        }

        $this->_twitteruser = isset($this->_config['twitter.user']) ?
                    $this->_config['twitter.user'] :
                    null;
        $this->_twitterpassword = isset($this->_config['twitter.password']) ?
            $this->_config['twitter.password'] :
            null;
        $url = isset($this->_config['twitter.url']) ?
            $this->_config['twitter.url'] :
            null;

        $this->_twitter = new $twitterClass(
            $this->_twitteruser,
            $this->_twitterpassword,
            $url
        );

    }

    /**
     * Fetches the associated tweet and relays it to the channel
     *
     * @param string $tweeter if numeric the tweet number/id, otherwise the twitter user name (optionally prefixed with @)
     * @param int $num optional tweet number for this user (number of tweets ago)
     * @return void
     */
    public function onCommandTwitter($tweeter = null, $num = 1)
    {
        $source = $this->getEvent()->getSource();
        if (is_numeric($tweeter)) {
            $tweet = $this->_twitter->getTweetByNum($tweeter);
        } else if (is_null($tweeter) && $this->_twitteruser) {
            $tweet = $this->_twitter->getLastTweet($this->_twitteruser, 1);
        } else {
            $tweet = $this->_twitter->getLastTweet(ltrim($tweeter, '@'), $num);
        }
        if ($tweet) {
            $this->doPrivmsg($source, $this->formatTweet($tweet));
        }
    }
    
    /**
     * Sends a tweet to Twitter as the configured user
     *
     * @param string $txt the text to tweet
     * @return void
     */
    public function onCommandTweet($txt) {
        $nick = $this->getEvent()->getNick();
        if (!$this->_twitteruser) {
            return;
        }
        if (self::$TWEET_REQUIRE_ADMIN && !$this->fromAdmin(true)) {
            return;
        }
        $source = $this->getEvent()->getSource();
        if ($tweet = $this->_twitter->sendTweet($txt)) {
            $this->doPrivmsg($source, 'Tweeted: '. $this->_twitter->getUrlOutputStatus($tweet));
        } else {
            $this->doNotice($nick, 'Tweet failed');
        }
    }
    
    /**
     * Formats a Tweet into a message suitable for output
     *
     * @param object $tweet
     * @return string
     */
    protected function formatTweet(StdClass $tweet, $includeUrl = true) {
        $out =  '<@' . $tweet->user->screen_name .'> '. $tweet->text
            . ' - ' . $this->getCountdown(time() - strtotime($tweet->created_at)) . ' ago';
        if ($includeUrl) {
            $out .= ' (' . $this->_twitter->getUrlOutputStatus($tweet) . ')';
        }
        return $out;
    }

    /**
     * Converts a given integer/timestamp into days, minutes and seconds
     *
     * Borrowed from Phergie 1.x
     *
     * @param int $time The time/integer to calulate the values from
     * @return string
     */
    public function getCountdown($time)
    {
        $return = array();

        $days = floor($time / 86400);
        if ($days > 0) {
            $return[] = $days . 'd';
            $time %= 86400;
        }

        $hours = floor($time / 3600);
        if ($hours > 0) {
            $return[] = $hours . 'h';
            $time %= 3600;
        }

        $minutes = floor($time / 60);
        if ($minutes > 0) {
            $return[] = $minutes . 'm';
            $time %= 60;
        }

        if ($time > 0 || count($return) <= 0) {
            $return[] = ($time > 0 ? $time : '0') . 's';
        }

        return implode(' ', $return);
    }

    /**
     * Renders a URL
     */
    public function renderUrl(array $parsed) {
        if ($parsed['host'] != 'twitter.com' && $parsed['host'] != 'www.twitter.com') {
            // unable to render non-twitter URLs
            return false;
        }

        $source = $this->getEvent()->getSource();

        if (preg_match('#^/(.*?)/status(es)?/([0-9]+)$#', $parsed['path'], $matches)) {
            $tweet = $this->_twitter->getTweetByNum($matches[3]);
            if ($tweet) {
                $this->doPrivmsg($source, $this->formatTweet($tweet, false));
            }
            return true;
        }

        // if we get this far, we haven't satisfied the URL, so bail:
        return false;

    }
}
