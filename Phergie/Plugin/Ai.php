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
 * @package   Phergie_Plugin_Ai
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2011 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Plugin_Ai
 */

/**
 * These requires are for library code, so they don't fit Autoload's normal
 * conventions.
 */
require dirname(__FILE__) . '/Ai/Pandora.class.php';
require dirname(__FILE__) . '/Ai/Markov.class.php';
require dirname(__FILE__) . '/Ai/Multi.class.php';

/**
 * Allows bot to use AI
 *
 * Usage:
 *   Bot: hello
 *
 * @category Phergie
 * @package  Phergie_Plugin_Ai
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Plugin_Ai
 */
class Phergie_Plugin_Ai extends Phergie_Plugin_Abstract
{
    /**
     * Ai object
     */
    protected $Ai;

    /**
     * Initialize
     *
     * @return void
     */
    public function onLoad()
    {
        $this->setAiMethod();
        $this->getPluginHandler()->getPlugin('Message');
    }

    /**
     * Sets the class to use for AI (pandora,markov,multi)
     *
     * @return Phergie_Plugin_Ai Implements a fluent interface
     */
    public function setAiMethod()
    {
        $aiMethod = $this->getConfig('ai.method', 'PandoraBots');
        $this->ai = new $aiMethod();
        return $this;
    }

    /**
     * Return the AI method to use
     *
     * @return Ai Class
     */
    public function getAiMethod()
    {
        return $this->ai;
    }

    /**
     * Fetches the associated tweet and relays it to the channel.
     *
     * @param string $tweeter if numeric the tweet number/id, otherwise the
     *        Ai user name (optionally prefixed with @, or a URL to a
     *        tweet)
     * @param int    $num     optional offset for this user (number of
     *        tweets ago)
     *
     * @return void
     */
    public function onPrivmsg()
    {
        $event = $this->event;
        $source = $event->getSource();
        $nick = $event->getHostmask()->getNick();
        $msg = $this->plugins->message->getMessage();
        $ai = $this->getAiMethod();
        $nick = $event->getNick();

        if($msg != false) {
            $response = $ai->say($msg);
            if(count(explode("\n",$response)) < 2){
                $this->doPrivmsg($source, $nick.": ".trim($response));
            } else {
                $this->doPrivmsg($source, 'Probably');
            }
        }
    }
}
