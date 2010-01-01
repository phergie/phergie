<?php

/**
 * Data structure for a hostmask.
 */
class Phergie_Hostmask
{
    /**
     * Host
     *
     * @var string
     */
    protected $_host;

    /**
     * Nick
     *
     * @var string
     */
    protected $_nick;

    /**
     * Username
     *
     * @var string
     */
    protected $_username;

    /**
     * Regular expression used to parse a hostmask
     *
     * @var string
     */
    protected static $_regex = '/^([^!@]+)!(?:[ni]=)?([^@]+)@([^ ]+)/';

    /**
     * Constructor to initialize components of the hostmask.
     *
     * @param string $nick Nick component
     * @param string $username Username component
     * @param string $host Host component
     */
    public function __construct($nick, $username, $host)
    {
        $this->_nick = $nick;
        $this->_username = $username;
        $this->_host = $host;
    }

    /**
     * Returns whether a given string appears to be a valid hostmask.
     *
     * @param string $string Alleged hostmask string
     * @return bool TRUE if the string appears to be a valid hostmask, FALSE 
     *         otherwise
     */
    public static function isValid($string)
    {
        return (preg_match(self::$_regex, $string) > 0);
    }

    /**
     * Parses a string containing the entire hostmask into a new instance of 
     * this class.
     *
     * @param string $hostmask Entire hostmask including the nick, username, 
     *        and host components
     * @return Phergie_Hostmask New instance populated with data parsed from 
     *         the provided hostmask string
     * @throws Phergie_Hostmask_Exception
     */
    public static function fromString($hostmask)
    {
        if (preg_match(self::$_regex, $hostmask, $match)) {
            list(, $nick, $username, $host) = $match; 
            return new self($nick, $username, $host);
        }

        throw new Phergie_Hostmask_Exception(
            'Invalid hostmask specified: "' . $hostmask . '"',
            Phergie_Hostmask_Exception::ERR_INVALID_HOSTMASK
        );
    }

    /**
     * Sets the host name.
     *
     * @param string $host
     * @return Phergie_Hostmask Provides a fluent interface
     */
    public function setHost($host)
    {
        $this->_host = $host;

        return $this;
    }

    /**
     * Returns the host name.
     *
     * @return string
     */
    public function getHost()
    {
        return $this->_host;
    }

    /**
     * Sets the username of the user.
     *
     * @param string $username
     * @return Phergie_Hostmask Provides a fluent interface
     */
    public function setUsername($username)
    {
        $this->_username = $username;

        return $this;
    }

    /**
     * Returns the username of the user.
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->_username;
    }

    /**
     * Sets the nick of the user.
     *
     * @param string $nick
     * @return Phergie_Hostmask Provides a fluent interface
     */
    public function setNick($nick)
    {
        $this->_nick = $nick;

        return $this;
    }

    /**
     * Returns the nick of the user.
     *
     * @return string
     */
    public function getNick()
    {
        return $this->_nick;
    }

    /**
     * Returns the hostmask for the originating server or user.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->_nick . '!' . $this->_username . '@' . $this->_host;
    }
}
