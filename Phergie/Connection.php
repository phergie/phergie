<?php

/**
 * Data structure for connection metadata.
 */
class Phergie_Connection
{
    /**
     * Host to which the client will connect
     *
     * @var string
     */
    protected $_host;

    /**
     * Port on which the client will connect, defaults to the standard IRC 
     * port
     *
     * @var int
     */
    protected $_port;

    /**
     * Flag where TRUE indicates that the connection should use SSL 
     *
     * @var bool
     */
    protected $_ssl;

    /**
     * Nick that the client will use
     *
     * @var string
     */
    protected $_nick;

    /**
     * Username that the client will use
     *
     * @var string
     */
    protected $_username;

    /**
     * Realname that the client will use
     *
     * @var string
     */
    protected $_realname;

    /**
     * Password that the client will use
     *
     * @var string
     */
    protected $_password;

    /**
     * Hostmask for the connection
     *
     * @var Phergie_Hostmask
     */
    protected $_hostmask;

    /**
     * Constructor to initialize instance properties.
     *
     * @param array $options Optional associative array of property values 
     *        to initialize
     * @return void
     */
    public function __construct(array $options = array())
    {
        $this->_ssl = false;

        $this->setOptions($options);
    }

    /**
     * Emits an error related to a required connection setting does not have
     * value set for it.
     *
     * @param string $setting Name of the setting
     * @return void
     */
    protected function _checkSetting($setting)
    {
        if (empty($this->{'_' . $setting})) {
            throw new Phergie_Connection_Exception(
                'Required connection setting "' . $setting . '" missing',
                Phergie_Connection_Exception::ERR_REQUIRED_SETTING_MISSING
            );
        }
    }
 
    /**
     * Returns a hostmask that uniquely identifies the connection.
     *
     * @return string
     */
    public function getHostmask()
    {
        if (empty($this->_hostmask)) {
            $this->_hostmask = new Phergie_Hostmask(
                $this->_nick,
                $this->_username,
                $this->_host
            );
        }

        return $this->_hostmask; 
    }

    /**
     * Sets the host to which the client will connect.
     *
     * @param string $host
     * @return Phergie_Connection Provides a fluent interface 
     */
    public function setHost($host)
    {
        if (empty($this->_host)) {
            $this->_host = (string) $host;
        }

        return $this;
    }
   
    /**
     * Returns the host to which the client will connect if it is set or 
     * emits an error if it is not set.
     *
     * @return string
     */
    public function getHost()
    {
        $this->_checkSetting('host');

        return $this->_host;
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
     * Sets whether the connection should use SSL.
     *
     * @param bool $ssl TRUE to use SSL, FALSE otherwise
     * @return Phergie_Connection Provides a fluent interface
     */
    public function setSsl($ssl)
    {
        $this->_ssl = (bool) $ssl;

        return $this;
    }

    /**
     * Returns whether the connection uses SSL.
     *
     * @return bool TRUE if the connection uses SSL, FALSE otherwise
     */
    public function getSsl()
    {
        return $this->_ssl;
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
