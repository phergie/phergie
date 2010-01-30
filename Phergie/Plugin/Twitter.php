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
    protected function formatTweet(StdClass $tweet) {
        return '<@' . $tweet->user->screen_name .'> '. $tweet->text
            . ' - ' . $this->getCountdown(time() - strtotime($tweet->created_at)) . ' ago'
            . ' (' . $this->_twitter->getUrlOutputStatus($tweet) . ')';
    }
}
