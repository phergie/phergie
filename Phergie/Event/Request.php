<?php

require_once 'Phergie/Event/Interface.php';

/**
 * Autonomous event originating from a user or the server.
 *
 * @see http://www.irchelp.org/irchelp/rfc/chapter4.html
 */
class Phergie_Event_Request implements ArrayAccess, Phergie_Event_Interface
{
    /**
     * Nick message
     *
     * @const string
     */
    const TYPE_NICK = 'nick';

    /**
     * Whois message
     *
     * @const string
     */
    const TYPE_WHOIS = 'whois';

    /**
     * Quit command
     *
     * @const string
     */
    const TYPE_QUIT = 'quit';

    /**
     * Join message
     *
     * @const string
     */
    const TYPE_JOIN = 'join';

    /**
     * Kick message
     *
     * @const string
     */
    const TYPE_KICK = 'kick';

    /**
     * Part message
     *
     * @const string
     */
    const TYPE_PART = 'part';

    /**
     * Mode message
     *
     * @const string
     */
    const TYPE_MODE = 'mode';

    /**
     * Topic message
     *
     * @const string
     */
    const TYPE_TOPIC = 'topic';

    /**
     * Private message command
     *
     * @const string
     */
    const TYPE_PRIVMSG = 'privmsg';

    /**
     * Notice message
     *
     * @const string
     */
    const TYPE_NOTICE = 'notice';

    /**
     * Pong message
     *
     * @const string
     */
    const TYPE_PONG = 'pong';

    /**
     * CTCP ACTION command
     *
     * @const string
     */
    const TYPE_ACTION = 'action';

    /**
     * CTCP PING command
     *
     * @const string
     */
    const TYPE_PING = 'ping';

    /**
     * CTCP TIME command
     *
     * @const string
     */
    const TYPE_TIME = 'time';

    /**
     * CTCP VERSION command
     *
     * @const string
     */
    const TYPE_VERSION = 'version';

    /**
     * RAW message
     *
     * @const string
     */
    const TYPE_RAW = 'raw';

    /**
     * Mapping of event types to their named parameters
     *
     * @var array
     */
    protected static $_map = array(

        self::TYPE_QUIT => array(
            'message' => 0
        ),

        self::TYPE_JOIN => array(
            'channel' => 0
        ),

        self::TYPE_KICK => array(
            'channel' => 0,
            'user'    => 1,
            'comment' => 2
        ),

        self::TYPE_PART => array(
            'channel' => 0,
            'message' => 1
        ),

        self::TYPE_MODE => array(
            'target'   => 0,
            'mode'     => 1,
            'limit'    => 2,
            'user'     => 3,
            'banmask' => 4
        ),

        self::TYPE_TOPIC => array(
            'channel' => 0,
            'topic'   => 1
        ),

        self::TYPE_PRIVMSG => array(
            'receiver' => 0,
            'text'     => 1
        ),

        self::TYPE_NOTICE => array(
            'nickname' => 0,
            'text'     => 1
        ),

        self::TYPE_ACTION => array(
            'target' => 0,
            'action' => 1
        ),

        self::TYPE_RAW => array(
            'message' => 0
        )

    );

    /**
     * Host name for the originating server or user
     *
     * @var string
     */
    protected $_host;

    /**
     * Username of the user from which the event originates
     *
     * @var string
     */
    protected $_username;

    /**
     * Nick of the user from which the event originates
     *
     * @var string
     */
    protected $_nick;

    /**
     * Request type, which can be compared to the TYPE_* class constants
     *
     * @var string
     */
    protected $_type;

    /**
     * Arguments included with the message
     *
     * @var array
     */
    protected $_arguments;

    /**
     * The raw buffer that was sent by the server
     *
     * @var string
     */
    protected $_rawBuffer;

    /**
     * Returns the hostmask for the originating server or user.
     *
     * @return string
     */
    public function getHostmask()
    {
        return $this->_nick . '!' . $this->_username . '@' . $this->_host;
    }

    /**
     * Sets the host name for the originating server or user.
     *
     * @param string $host
     * @return Phergie_Event_Request Provides a fluent interface
     */
    public function setHost($host)
    {
        $this->_host = $host;
        return $this;
    }

    /**
     * Returns the host name for the originating server or user.
     *
     * @return string
     */
    public function getHost()
    {
        return $this->_host;
    }

    /**
     * Sets the username of the user from which the event originates.
     *
     * @param string $username
     * @return Phergie_Event_Request Provides a fluent interface
     */
    public function setUsername($username)
    {
        $this->_username = $username;
        return $this;
    }

    /**
     * Returns the username of the user from which the event originates.
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->_username;
    }

    /**
     * Sets the nick of the user from which the event originates.
     *
     * @param string $nick
     * @return Phergie_Event_Request Provides a fluent interface
     */
    public function setNick($nick)
    {
        $this->_nick = $nick;
        return $this;
    }

    /**
     * Returns the nick of the user from which the event originates.
     *
     * @return string
     */
    public function getNick()
    {
        return $this->_nick;
    }

    /**
     * Sets the request type.
     *
     * @param string $type
     * @return Phergie_Event_Request Provides a fluent interface
     */
    public function setType($type)
    {
        $this->_type = strtolower($type);
        return $this;
    }

    /**
     * Returns the request type, which can be compared to the TYPE_*
     * class constants.
     *
     * @return string
     */
    public function getType()
    {
        return $this->_type;
    }

    /**
     * Sets the arguments for the request in the order they are to be sent.
     *
     * @param array $arguments
     * @return Phergie_Event_Request Provides a fluent interface
     */
    public function setArguments($arguments)
    {
        $this->_arguments = $arguments;
        return $this;
    }

    /**
     * Returns the arguments for the request in the order they are to be sent.
     *
     * @return array
     */
    public function getArguments()
    {
        return $this->_arguments;
    }

    /**
     * Resolves an argument specification to an integer position.
     *
     * @param mixed $argument Integer position (starting from 0) or the
     *        equivalent string name of the argument from self::$_map
     * @return int|null Integer position of the argument or NULL if no 
     *         corresponding argument was found
     */
    private function _resolveArgument($argument)
    {
        if (isset($this->_arguments[$argument])) {
            return $argument; 
        } else {
            $argument = strtolower($argument);
            if (isset(self::$_map[$this->_type][$argument]) &&
                isset($this->_arguments[self::$_map[$this->_type][$argument]])) {
                return self::$_map[$this->_type][$argument];
            }
        }
        return null;
    }

    /**
     * Returns a single specified argument for the request.
     *
     * @param mixed $argument Integer position (starting from 0) or the
     *        equivalent string name of the argument from self::$_map
     * @return string
     */
    public function getArgument($argument)
    {
        if ($argument = $this->_resolveArgument($argument)) {
            return $this->_arguments[$argument];
        }
        return null;
    }

    /**
     * Sets the raw buffer for the event.
     *
     * @param string $buffer
     * @return Phergie_Event_Request Provides a fluent interface
     */
    public function setRawBuffer($buffer)
    {
        $this->_rawBuffer = $buffer;
        return $this;
    }

    /**
     * Returns the raw buffer sent from the server for the event.
     *
     * @return string
     */
    public function getRawBuffer()
    {
        return $this->_rawBuffer;
    }

    /**
     * Returns the channel name or user nick representing the source of the
     * event.
     *
     * @return string
     */
    public function getSource()
    {
        if (substr($this->_arguments[0], 0, 1) == '#') {
            return $this->_arguments[0];
        }
        return $this->_nick;
    }

    /**
     * Returns whether or not the event occurred within a channel.
     *
     * @return TRUE if the event is in a channel, FALSE otherwise
     */
    public function isInChannel()
    {
        return (substr($this->getSource(), 0, 1) == '#');
    }

    /**
     * Returns whether or not the event originated from a user.
     *
     * @return TRUE if the event is from a user, FALSE otherwise
     */
    public function isFromUser()
    {
        return !empty($this->_username);
    }

    /**
     * Returns whether or not the event originated from the server.
     *
     * @return TRUE if the event is from the server, FALSE otherwise
     */
    public function isFromServer()
    {
        return empty($this->_username);
    }

    /**
     * Provides access to named parameters via virtual "getter" methods.
     *
     * @param string $name Name of the method called
     * @param array $arguments Arguments passed to the method (should always
     *                         be empty)
     * @return mixed Method return value
     */
    public function __call($name, array $arguments)
    {
        if (!count($arguments) && substr($name, 0, 3) == 'get') {
            return $this->getArgument(substr($name, 3));
        }
    }

    /**
     * @see ArrayAccess::offsetExists()
     */
    public function offsetExists($offset)
    {
        return ($this->_resolveArgument($offset) !== null);
    }

    /**
     * @see ArrayAccess::offsetGet()
     */
    public function offsetGet($offset)
    {
        return $this->getArgument($offset);
    }

    /**
     * @see ArrayAccess::offsetSet()
     */
    public function offsetSet($offset, $value)
    {
        if ($offset = $this->_resolveArgument($offset)) {
            $this->_arguments[$offset] = $value;
        }
    }

    /**
     * @see ArrayAccess::offsetUnset()
     */
    public function offsetUnset($offset)
    {
        if ($offset = $this->_resolveArgument($offset)) {
            unset($this->_arguments[$offset]);
        }
    }
}
