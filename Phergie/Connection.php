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
    protected $host;

    /**
     * Port on which the client will connect, defaults to the standard IRC 
     * port
     *
     * @var int
     */
    protected $port;

    /**
     * Flag where TRUE indicates that the connection should use SSL 
     *
     * @var bool
     */
    protected $ssl;

    /**
     * Nick that the client will use
     *
     * @var string
     */
    protected $nick;

    /**
     * Username that the client will use
     *
     * @var string
     */
    protected $username;

    /**
     * Realname that the client will use
     *
     * @var string
     */
    protected $realname;

    /**
     * Password that the client will use
     *
     * @var string
     */
    protected $password;

    /**
     * Hostmask for the connection
     *
     * @var Phergie_Hostmask
     */
    protected $hostmask;

    /**
     * Constructor to initialize instance properties.
     *
     * @param array $options Optional associative array of property values 
     *        to initialize
     *
     * @return void
     */
    public function __construct(array $options = array())
    {
        $this->ssl = false;

        $this->setOptions($options);
    }

    /**
     * Emits an error related to a required connection setting does not have
     * value set for it.
     *
     * @param string $setting Name of the setting
     *
     * @return void
     */
    protected function checkSetting($setting)
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
        if (empty($this->hostmask)) {
            $this->hostmask = new Phergie_Hostmask(
                $this->nick,
                $this->username,
                $this->host
            );
        }

        return $this->hostmask; 
    }

    /**
     * Sets the host to which the client will connect.
     *
     * @param string $host Hostname
     *
     * @return Phergie_Connection Provides a fluent interface 
     */
    public function setHost($host)
    {
        if (empty($this->host)) {
            $this->host = (string) $host;
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
        $this->checkSetting('host');

        return $this->host;
    }

    /**
     * Sets the port on which the client will connect.
     *
     * @param int $port Port
     *
     * @return Phergie_Connection Provides a fluent interface 
     */
    public function setPort($port)
    {
        if (empty($this->port)) {
            $this->port = (int) $port;
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
        if (empty($this->port)) {
            $this->port = 6667;
        }

        return $this->port;
    }

    /**
     * Sets whether the connection should use SSL.
     *
     * @param bool $ssl TRUE to use SSL, FALSE otherwise
     *
     * @return Phergie_Connection Provides a fluent interface
     */
    public function setSsl($ssl)
    {
        $this->ssl = (bool) $ssl;

        return $this;
    }

    /**
     * Returns whether the connection uses SSL.
     *
     * @return bool TRUE if the connection uses SSL, FALSE otherwise
     */
    public function getSsl()
    {
        return $this->ssl;
    }

    /**
     * Sets the nick that the client will use.
     *
     * @param string $nick Nickname
     *
     * @return Phergie_Connection Provides a fluent interface 
     */
    public function setNick($nick)
    {
        if (empty($this->nick)) {
            $this->nick = (string) $nick;
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
        $this->checkSetting('nick');

        return $this->nick;
    }

    /**
     * Sets the username that the client will use.
     *
     * @param string $username Username
     *
     * @return Phergie_Connection Provides a fluent interface 
     */
    public function setUsername($username)
    {
        if (empty($this->username)) {
            $this->username = (string) $username;
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
        $this->checkSetting('username');

        return $this->username;
    }

    /**
     * Sets the realname that the client will use.
     *
     * @param string $realname Real name
     *
     * @return Phergie_Connection Provides a fluent interface 
     */
    public function setRealname($realname)
    {
        if (empty($this->realname)) {
            $this->realname = (string) $realname;
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
        $this->checkSetting('realname');

        return $this->realname;
    }

    /**
     * Sets the password that the client will use.
     *
     * @param string $password Password
     *
     * @return Phergie_Connection Provides a fluent interface 
     */
    public function setPassword($password)
    {
        if (empty($this->password)) {
            $this->password = (string) $password;
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
        return $this->password;
    }

    /**
     * Sets multiple connection settings using an array.
     *
     * @param array $options Associative array of setting names mapped to 
     *        corresponding values
     *
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
