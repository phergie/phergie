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
 * @package   Phergie_Plugin_NickServ
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2010 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Plugin_NickServ
 */

/**
 * Intercepts and responds to messages from the NickServ agent requesting that
 * the bot authenticate its identify.
 *
 * The password configuration setting should contain the password registered
 * with NickServ for the nick used by the bot.
 *
 * @category Phergie
 * @package  Phergie_Plugin_NickServ
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Plugin_NickServ
 * @uses     Phergie_Plugin_Command pear.phergie.org
 */
class Phergie_Plugin_NickServ extends Phergie_Plugin_Abstract
{
    /**
     * Nick of the NickServ bot
     *
     * @var string
     */
    protected $botNick;

    /**
    * Identify message
    */
    protected $identifyMessage;

    /**
     * Checks for dependencies and required configuration settings.
     *
     * @return void
     */
    public function onLoad()
    {
        $this->getPluginHandler()->getPlugin('Command');

        // Get the name of the NickServ bot, defaults to NickServ
        $this->botNick = $this->config['nickserv.botnick'];
        if (!$this->botNick) {
            $this->botNick = 'NickServ';
        }

        // Get the identify message
        $this->identifyMessage = $this->config['nickserv.identify_message'];
        if (!$this->identifyMessage) {
            $this->identifyMessage = 'This nickname is registered.';
        }
    }

    /**
     * Checks for a notice from NickServ and responds accordingly if it is an
     * authentication request or a notice that a ghost connection has been
     * killed.
     *
     * @return void
     */
    public function onNotice()
    {
        $event = $this->event;
        if (strtolower($event->getNick()) == strtolower($this->botNick)) {
            $message = $event->getArgument(1);
            $nick = $this->connection->getNick();
            if (strpos($message, $this->identifyMessage) !== false) {
                $password = $this->config['nickserv.password'];
                if (!empty($password)) {
                    $this->doPrivmsg($this->botNick, 'IDENTIFY ' . $password);
                }
                unset($password);
            } elseif (preg_match('/^.*' . $nick . '.* has been killed/', $message)) {
                $this->doNick($nick);
            }
        }
    }

    /**
     * Checks to see if the original nick has quit; if so, take the name back.
     *
     * @return void
     */
    public function onQuit()
    {
        $eventNick = $this->event->getNick();
        $nick = $this->connection->getNick();
        if ($eventNick == $nick) {
            $this->doNick($nick);
        }
    }

    /**
     * Changes the in-memory configuration setting for the bot nick if it is
     * successfully changed.
     *
     * @return void
     */
    public function onNick()
    {
        $event = $this->event;
        $connection = $this->connection;
        if ($event->getNick() == $connection->getNick()) {
            $connection->setNick($event->getArgument(0));
        }
    }

    /**
     * Provides a command to terminate ghost connections.
     *
     * @return void
     */
    public function onCommandGhostbust()
    {
        $event = $this->event;
        $user = $event->getNick();
        $conn = $this->connection;
        $nick = $conn->getNick();

        if ($nick != $this->config['connections'][$conn->getHost()]['nick']) {
            $password = $this->config['nickserv.password'];
            if (!empty($password)) {
                $this->doPrivmsg(
                    $this->event->getSource(),
                    $user . ': Attempting to ghost ' . $nick .'.'
                );
                $this->doPrivmsg(
                    $this->botNick,
                    'GHOST ' . $nick . ' ' . $password,
                    true
                );
            }
        }
    }

    /**
     * Automatically send the GHOST command if the bot's nick is in use.
     *
     * @return void
     */
    public function onResponse()
    {
        if ($this->event->getCode() == Phergie_Event_Response::ERR_NICKNAMEINUSE) {
            $password = $this->config['nickserv.password'];
            if (!empty($password)) {
                $this->doPrivmsg(
                    $this->botNick,
                    'GHOST ' . $this->connection->getNick() . ' ' . $password
                );
            }
        }
    }

    /**
     * Handle the server sending a KILL request.
     *
     * @return void
     */
    public function onKill()
    {
        $this->doQuit($this->event->getArgument(1));
    }
}
