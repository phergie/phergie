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
            isset($this->config['twitter.tweetrequireadmin']) &&
            $req = $this->config['twitter.tweetrequireadmin']
        ) {
            // if so, override default
            self::$TWEET_REQUIRE_ADMIN = $req;
        }
        if (
            !isset($this->config['twitter.class']) ||
            !$twitterClass = $this->config['twitter.class']
        ) {
            $twitterClass = 'Twitter';
        }

        $this->twitteruser = $this->config['twitter.user'];
        $this->twitterpassword = $this->config['twitter.password'];
        $url = $this->config['twitter.url'];

        $this->twitter = new $twitterClass(
            $this->twitteruser,
            $this->twitterpassword,
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
            $tweet = $this->twitter->getTweetByNum($tweeter);
        } else if (is_null($tweeter) && $this->twitteruser) {
            $tweet = $this->twitter->getLastTweet($this->twitteruser, 1);
        } else {
            $tweet = $this->twitter->getLastTweet(ltrim($tweeter, '@'), $num);
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
        echo "Tweet!\n";
        $nick = $this->getEvent()->getNick();
        if (!$this->twitteruser) {
            return;
        }
        if (self::$TWEET_REQUIRE_ADMIN && !$this->fromAdmin(true)) {
            return;
        }
        $source = $this->getEvent()->getSource();
        if ($tweet = $this->twitter->sendTweet($txt)) {
            $this->doPrivmsg($source, 'Tweeted: '. $this->twitter->getUrlOutputStatus($tweet));
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
        $ts = new Phergie_Plugin_Helper_Time($tweet->created_at);
        $out =  '<@' . $tweet->user->screen_name .'> '. $tweet->text
            . ' - ' . $ts->getCountDown() . ' ago';
        if ($includeUrl) {
            $out .= ' (' . $this->twitter->getUrlOutputStatus($tweet) . ')';
        }
        return $out;
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
