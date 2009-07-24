<?php

/**
 * Data structure for connection metadata.
 */
class Phergie_Connection
{
    /**
     * Hostname to which the client will connect
     *
     * @var string
     */
    private $_hostname;

    /**
     * Port on which the client will connect, defaults to the standard IRC 
     * port
     *
     * @var int
     */
    private $_port;

    /**
     * Nick that the client will use
     *
     * @var string
     */
    private $_nick;

    /**
     * Username that the client will use
     *
     * @var string
     */
    private $_username;

    /**
     * Realname that the client will use
     *
     * @var string
     */
    private $_realname;

    /**
     * Password that the client will use
     *
     * @var string
     */
    private $_password;

    /**
     * Constructor to initialize instance properties.
     *
     * @param array $options Optional associative array of property values 
     *        to initialize
     * @return void
     */
    public function __construct(array $options = array())
    {
        $this->setOptions($options);
    }

    /**
     * Returns a hostmask that uniquely identifies the connection.
     *
     * @return string
     */
    public function getHostmask()
    {
        return ($this->_nick . '!' . $this->_username . '@' . $this->_hostname);
    }

    /**
     * Sets the hostname to which the client will connect.
     *
     * @param string $hostname
     * @return Phergie_Connection Provides a fluent interface 
     */
    public function setHostname($hostname)
    {
        if (empty($this->_hostname)) {
            $this->_hostname = (string) $hostname;
        }

        return $this;
    }

    /**
     * Emits an error related to a required connection setting does not have
     * value set for it.
     *
     * @param string $setting Name of the setting
     * @return void
     */
    private function _checkSetting($setting)
    {
        if (empty($this->{'_' . $setting})) {
            trigger_error('Required connection setting missing: ' . $setting, E_USER_ERROR);
        }
    }
    
    /**
     * Returns the hostname to which the client will connect if it is set or 
     * emits an error if it is not set.
     *
     * @return string
     */
    public function getHostname()
    {
        $this->_checkSetting('hostname');

        return $this->_hostname;
    }

    /**
     * Sets the port on which the client will connect.
     *
     * @param int $port
     * @return Phergie_Connection Provides a fluent interface 
     */
    public function setPort($port)
    {
        if (empty($this->_port)) {
            $this->_port = (int) $port;
        }

        return $this;
    }

    /**
     * Returns the port on which the client will connect.
     *
     * @return int
     */
    public function getPort()
    {
        if (empty($this->_port)) {
            $this->_port = 6667;
        }

        return $this->_port;
    }

    /**
     * Sets the nick that the client will use.
     *
     * @param string $nick
     * @return Phergie_Connection Provides a fluent interface 
     */
    public function setNick($nick)
    {
        if (empty($this->_nick)) {
            $this->_nick = (string) $nick;
        }

        return $this;
    }

    /**
     * Returns the nick that the client will use.
     *
     * @return string
     */
    public function getNick()
    {
        $this->_checkSetting('nick');

        return $this->_nick;
    }

    /**
     * Sets the username that the client will use.
     *
     * @param string $username
     * @return Phergie_Connection Provides a fluent interface 
     */
    public function setUsername($username)
    {
        if (empty($this->_username)) {
            $this->_username = (string) $username;
        }

        return $this;
    }

    /**
     * Returns the username that the client will use.
     *
     * @return string
     */
    public function getUsername()
    {
        $this->_checkSetting('username');

        return $this->_username;
    }

    /**
     * Sets the realname that the client will use.
     *
     * @param string $realname
     * @return Phergie_Connection Provides a fluent interface 
     */
    public function setRealname($realname)
    {
        if (empty($this->_realname)) {
            $this->_realname = (string) $realname;
        }

        return $this;
    }

    /**
     * Returns the realname that the client will use.
     *
     * @return string
     */
    public function getRealname()
    {
        $this->_checkSetting('realname');

        return $this->_realname;
    }

    /**
     * Sets the password that the client will use.
     *
     * @param string $password
     * @return Phergie_Connection Provides a fluent interface 
     */
    public function setPassword($password)
    {
        if (empty($this->_password)) {
            $this->_password = (string) $password;
        }

        return $this;
    }

    /**
     * Returns the password that the client will use.
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->_password;
    }

    /**
     * Sets multiple connection settings using an array.
     *
     * @param array $options Associative array of setting names mapped to 
     *        corresponding values
     * @return Phergie_Connection Provides a fluent interface 
     */
    public function setOptions(array $options)
    {
        foreach ($options as $option => $value) {
            $method = 'set' . ucfirst($option);
            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }
    }
}
