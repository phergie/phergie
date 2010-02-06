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
 * @package   Phergie
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2010 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie
 */

/**
 * Data structure for a hostmask.
 *
 * @category Phergie 
 * @package  Phergie
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie
 */
class Phergie_Hostmask
{
    /**
     * Host
     *
     * @var string
     */
    protected $host;

    /**
     * Nick
     *
     * @var string
     */
    protected $nick;

    /**
     * Username
     *
     * @var string
     */
    protected $username;

    /**
     * Regular expression used to parse a hostmask
     *
     * @var string
     */
    protected static $regex = '/^([^!@]+)!(?:[ni]=)?([^@]+)@([^ ]+)/';

    /**
     * Constructor to initialize components of the hostmask.
     *
     * @param string $nick     Nick component
     * @param string $username Username component
     * @param string $host     Host component
     *
     * @return void
     */
    public function __construct($nick, $username, $host)
    {
        $this->nick = $nick;
        $this->username = $username;
        $this->host = $host;
    }

    /**
     * Returns whether a given string appears to be a valid hostmask.
     *
     * @param string $string Alleged hostmask string
     *
     * @return bool TRUE if the string appears to be a valid hostmask, FALSE 
     *         otherwise
     */
    public static function isValid($string)
    {
        return (preg_match(self::$regex, $string) > 0);
    }

    /**
     * Parses a string containing the entire hostmask into a new instance of 
     * this class.
     *
     * @param string $hostmask Entire hostmask including the nick, username, 
     *        and host components
     *
     * @return Phergie_Hostmask New instance populated with data parsed from 
     *         the provided hostmask string
     * @throws Phergie_Hostmask_Exception
     */
    public static function fromString($hostmask)
    {
        if (preg_match(self::$regex, $hostmask, $match)) {
            list(, $nick, $username, $host) = $match; 
            return new self($nick, $username, $host);
        }

        throw new Phergie_Hostmask_Exception(
            'Invalid hostmask specified: "' . $hostmask . '"',
            Phergie_Hostmask_Exception::ERR_INVALID_HOSTMASK
        );
    }

    /**
     * Sets the hostname.
     *
     * @param string $host Hostname
     *
     * @return Phergie_Hostmask Provides a fluent interface
     */
    public function setHost($host)
    {
        $this->host = $host;

        return $this;
    }

    /**
     * Returns the hostname.
     *
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Sets the username of the user.
     *
     * @param string $username Username
     *
     * @return Phergie_Hostmask Provides a fluent interface
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Returns the username of the user.
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Sets the nick of the user.
     *
     * @param string $nick User nick
     *
     * @return Phergie_Hostmask Provides a fluent interface
     */
    public function setNick($nick)
    {
        $this->nick = $nick;

        return $this;
    }

    /**
     * Returns the nick of the user.
     *
     * @return string
     */
    public function getNick()
    {
        return $this->nick;
    }

    /**
     * Returns the hostmask for the originating server or user.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->nick . '!' . $this->username . '@' . $this->host;
    }
}
