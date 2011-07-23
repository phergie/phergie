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
 * @package   Phergie_Plugin_UserInfo
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2011 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Plugin_UserInfo
 */

/**
 * Provides an API for querying information on users.
 *
 * @category Phergie
 * @package  Phergie_Plugin_UserInfo
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Plugin_UserInfo
 */
class Phergie_Plugin_UserInfo extends Phergie_Plugin_Abstract
{
    const REGULAR = 1;
    const VOICE   = 2;
    const HALFOP  = 4;
    const OP      = 8;
    const ADMIN   = 16;
    const OWNER   = 32;

    /**
     * An array containing all the user information for a given channel
     *
     * @var array
     */
    protected $store = array();

    /**
     * Tracks mode changes
     *
     * @return void
     */
    public function onMode()
    {
        $args = $this->event->getArguments();

        if (count($args) != 3) {
            return;
        }

        list($chan, $modes, $nicks) = $args;

        if (!preg_match('/(?:\+|-)[hovaq+-]+/i', $modes)) {
            return;
        }

        $chan = trim(strtolower($chan));
        $modes = str_split(trim(strtolower($modes)), 1);
        $nicks = explode(' ', trim(strtolower($nicks)));
        $operation = array_shift($modes); // + or -

        while ($char = array_shift($modes)) {
            $nick = array_shift($nicks);
            $mode = null;

            switch ($char) {
            case 'q':
                $mode = self::OWNER;
                break;
            case 'a':
                $mode = self::ADMIN;
                break;
            case 'o':
                $mode = self::OP;
                break;
            case 'h':
                $mode = self::HALFOP;
                break;
            case 'v':
                $mode = self::VOICE;
                break;
            }

            if (!empty($mode)) {
                if ($operation == '+') {
                    $this->store[$chan][$nick] |= $mode;
                } else if ($operation == '-') {
                    $this->store[$chan][$nick] ^= $mode;
                }
            }
        }
    }

    /**
     * Tracks users joining a channel
     *
     * @return void
     */
    public function onJoin()
    {
        $chan = trim(strtolower($this->event->getArgument(0)));
        $nick = trim(strtolower($this->event->getNick()));

        $this->store[$chan][$nick] = self::REGULAR;
    }

    /**
     * Tracks users leaving a channel
     *
     * @return void
     */
    public function onPart()
    {
        $chan = trim(strtolower($this->event->getArgument(0)));
        $nick = trim(strtolower($this->event->getNick()));

        if (isset($this->store[$chan][$nick])) {
            unset($this->store[$chan][$nick]);
        }
    }

    /**
     * Tracks users quitting a server
     *
     * @return void
     */
    public function onQuit()
    {
        $nick = trim(strtolower($this->event->getNick()));

        foreach ($this->store as $chan => $store) {
            if (isset($store[$nick])) {
                unset($this->store[$chan][$nick]);
            }
        }
    }

    /**
     * Tracks users changing nicks
     *
     * @return void
     */
    public function onNick()
    {
        $nick = trim(strtolower($this->event->getNick()));
        $newNick = trim(strtolower($this->event->getArgument(0)));

        foreach ($this->store as $chan => $store) {
            if (isset($store[$nick])) {
                $this->store[$chan][$newNick] = $store[$nick];
                unset($this->store[$chan][$nick]);
            }
        }
    }

    /**
     * Populates the internal user listing for a channel when the bot joins it.
     *
     * @return void
     */
    public function onResponse()
    {
        if ($this->event->getCode() != Phergie_Event_Response::RPL_NAMREPLY) {
            return;
        }

        $array = explode(' ', $this->event->getDescription());
        $chan  = $array[1];
        $count = count($array);

        for ($i = 3; $i < $count; $i++) {

            if (empty($array[$i])) {
                continue;
            }

            $user = trim(strtolower($array[$i]));

            $flag = self::REGULAR;
            if ($user[0] == '~') {
                $flag |= self::OWNER;
            } else if ($user[0] == '&') {
                $flag |= self::ADMIN;
            } else if ($user[0] == '@') {
                $flag |= self::OP;
            } else if ($user[0] == '%') {
                $flag |= self::HALFOP;
            } else if ($user[0] == '+') {
                $flag |= self::VOICE;
            }

            if ($flag != self::REGULAR) {
                $user = substr($user, 1);
            }

            $this->store[$chan][$user] = $flag;
        }
    }

    /**
     * Debugging function
     *
     * @return void
     */
    public function onPrivmsg()
    {
        if ($this->getConfig('debug', false) == false) {
            return;
        }

        list($target, $msg) = array_pad($this->event->getArguments(), 2, null);

        if (preg_match('#^ishere (\S+)$#', $msg, $m)) {
            $this->doPrivmsg(
                $target, $this->isIn($m[1], $target) ? 'true' : 'false'
            );
        } elseif (preg_match('#^isowner (\S+)$#', $msg, $m)) {
            $this->doPrivmsg(
                $target, $this->isOwner($m[1], $target) ? 'true' : 'false'
            );
        } elseif (preg_match('#^isadmin (\S+)$#', $msg, $m)) {
            $this->doPrivmsg(
                $target, $this->isAdmin($m[1], $target) ? 'true' : 'false'
            );
        } elseif (preg_match('#^isop (\S+)$#', $msg, $m)) {
            $this->doPrivmsg(
                $target, $this->isOp($m[1], $target) ? 'true' : 'false'
            );
        } elseif (preg_match('#^ishop (\S+)$#', $msg, $m)) {
            $this->doPrivmsg(
                $target, $this->isHalfop($m[1], $target) ? 'true' : 'false'
            );
        } elseif (preg_match('#^isvoice (\S+)$#', $msg, $m)) {
            $this->doPrivmsg(
                $target, $this->isVoice($m[1], $target) ? 'true' : 'false'
            );
        } elseif (preg_match('#^channels (\S+)$#', $msg, $m)) {
            $channels = $this->getChannels($m[1]);
            $this->doPrivmsg(
                $target, $channels ? join(', ', $channels) : 'unable to find nick'
            );
        } elseif (preg_match('#^users (\S+)$#', $msg, $m)) {
            $nicks = $this->getUsers($m[1]);
            $this->doPrivmsg(
                $target, $nicks ? join(', ', $nicks) : 'unable to find channel'
            );
        } elseif (preg_match('#^random (\S+)$#', $msg, $m)) {
            $nick = $this->getrandomuser($m[1]);
            $this->doPrivmsg($target, $nick ? $nick : 'unable to  find channel');
        }
    }

    /**
     * Checks whether or not a given user has a mode
     *
     * @param int    $mode A numeric mode (identified by the class constants)
     * @param string $nick The nick to check
     * @param string $chan The channel to check in
     *
     * @return bool
     */
    public function is($mode, $nick, $chan)
    {
        $chan = trim(strtolower($chan));
        $nick = trim(strtolower($nick));

        if (!isset($this->store[$chan][$nick])) {
            return false;
        }

        return ($this->store[$chan][$nick] & $mode) != 0;
    }

    /**
     * Checks whether or not a given user has owner (~) status
     *
     * @param string $nick The nick to check
     * @param string $chan The channel to check in
     *
     * @return bool
     */
    public function isOwner($nick, $chan)
    {
        return $this->is(self::OWNER, $nick, $chan);
    }

    /**
     * Checks whether or not a given user has admin (&) status
     *
     * @param string $nick The nick to check
     * @param string $chan The channel to check in
     *
     * @return bool
     */
    public function isAdmin($nick, $chan)
    {
        return $this->is(self::ADMIN, $nick, $chan);
    }

    /**
     * Checks whether or not a given user has operator (@) status
     *
     * @param string $nick The nick to check
     * @param string $chan The channel to check in
     *
     * @return bool
     */
    public function isOp($nick, $chan)
    {
        return $this->is(self::OP, $nick, $chan);
    }

    /**
     * Checks whether or not a given user has halfop (%) status
     *
     * @param string $nick The nick to check
     * @param string $chan The channel to check in
     *
     * @return bool
     */
    public function isHalfop($nick, $chan)
    {
        return $this->is(self::HALFOP, $nick, $chan);
    }

    /**
     * Checks whether or not a given user has voice (+) status
     *
     * @param string $nick The nick to check
     * @param string $chan The channel to check in
     *
     * @return bool
     */
    public function isVoice($nick, $chan)
    {
        return $this->is(self::VOICE, $nick, $chan);
    }

    /**
     * Checks whether or not a given user is in a channel
     *
     * @param string $nick The nick to check
     * @param string $chan The channel to check in
     *
     * @return bool
     */
    public function isIn($nick, $chan)
    {
        return $this->is(self::REGULAR, $nick, $chan);
    }

    /**
     * Returns the entire user list for a channel or false if the bot is not
     * in the channel.
     *
     * @param string $chan The channel name
     *
     * @return array|bool
     */
    public function getUsers($chan)
    {
        $chan = trim(strtolower($chan));
        if (isset($this->store[$chan])) {
            return array_keys($this->store[$chan]);
        }
        return false;
    }

    /**
     * Returns the nick of a random user present in a given channel or false
     * if the bot is not present in the channel.
     * 
     * To exclude the bot's current nick, for example:
     *     $chan = $this->getEvent()->getSource();
     * 	   $current_nick = $this->getConnection()->getNick();
     * 	   $random_user = $this->plugins->getPlugin('UserInfo')
     *          ->getRandomUser( $chan, array( $current_nick ) );
     *
     * @param string $chan   The channel name
     * @param array  $ignore A list of nicks to ignore in the channel.
     *                       Useful for excluding the bot itself.
     *
     * @return string|bool
     */
    public function getRandomUser($chan, $ignore = array('chanserv'))
    {
        $chan = trim(strtolower($chan));

        if (isset($this->store[$chan])) {
            do {
                $nick = array_rand($this->store[$chan], 1);
            } while (in_array($nick, $ignore));

            return $nick;
        }

        return false;
    }

    /**
     * Returns a list of channels in which a given user is present.
     *
     * @param string $nick Nick of the user (optional, defaults to the bot's
     *               nick)
     *
     * @return array|bool
     */
    public function getChannels($nick = null)
    {
        if (empty($nick)) {
            $nick = $this->connection->getNick();
        }

        $nick = trim(strtolower($nick));
        $channels = array();

        foreach ($this->store as $chan => $store) {
            if (isset($store[$nick])) {
                $channels[] = $chan;
            }
        }

        return $channels;
    }
}
