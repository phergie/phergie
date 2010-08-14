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
 * Autonomous event originating from a user or the server.
 *
 * @category Phergie
 * @package  Phergie
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie
 * @link     http://www.irchelp.org/irchelp/rfc/chapter4.html
 */
class Phergie_Event_Request
    extends Phergie_Event_Abstract
    implements ArrayAccess
{
    /**
     * Nick message event type
     */
    const TYPE_NICK = 'nick';

    /**
     * Whois message event type
     */
    const TYPE_WHOIS = 'whois';

    /**
     * Quit command event type
     */
    const TYPE_QUIT = 'quit';

    /**
     * Join message event type
     */
    const TYPE_JOIN = 'join';

    /**
     * Kick message event type
     */
    const TYPE_KICK = 'kick';

    /**
     * Part message event type
     */
    const TYPE_PART = 'part';

    /**
     * Invite message event type
     */
    const TYPE_INVITE = 'invite';

    /**
     * Mode message event type
     */
    const TYPE_MODE = 'mode';

    /**
     * Topic message event type
     */
    const TYPE_TOPIC = 'topic';

    /**
     * Private message command event type
     */
    const TYPE_PRIVMSG = 'privmsg';

    /**
     * Notice message event type
     */
    const TYPE_NOTICE = 'notice';

    /**
     * Pong message event type
     */
    const TYPE_PONG = 'pong';

    /**
     * CTCP ACTION command event type
     */
    const TYPE_ACTION = 'action';

    /**
     * CTCP PING command event type
     */
    const TYPE_PING = 'ping';

    /**
     * CTCP TIME command event type
     */
    const TYPE_TIME = 'time';

    /**
     * CTCP VERSION command event type
     */
    const TYPE_VERSION = 'version';

    /**
     * RAW message event type
     */
    const TYPE_RAW = 'raw';

    /**
     * Mapping of event types to their named parameters
     *
     * @var array
     */
    protected static $map = array(

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

        self::TYPE_INVITE => array(
            'nickname' => 0,
            'channel'  => 1
        ),

        self::TYPE_MODE => array(
            'target'  => 0,
            'mode'    => 1,
            'limit'   => 2,
            'user'    => 3,
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
     * Hostmask representing the originating user, if applicable
     *
     * @var Phergie_Hostmask
     */
    protected $hostmask;

    /**
     * Arguments included with the message
     *
     * @var array
     */
    protected $arguments = array();

    /**
     * Raw data sent by the server
     *
     * @var string
     */
    protected $rawData;

    /**
     * Sets the hostmask representing the originating user.
     *
     * @param Phergie_Hostmask $hostmask User hostmask
     *
     * @return Phergie_Event_Request Provides a fluent interface
     */
    public function setHostmask(Phergie_Hostmask $hostmask)
    {
        $this->hostmask = $hostmask;
        return $this;
    }

    /**
     * Returns the hostmask representing the originating user.
     *
     * @return Phergie_Event_Request|null Hostmask or NULL if none was set
     */
    public function getHostmask()
    {
        if (empty($this->hostmask)) {
            throw new Phergie_Event_Exception(
                'Hostmask has not been set',
                Phergie_Event_Exception::ERR_MISSING_HOSTMASK
            );
        }
        return $this->hostmask;
    }

    /**
     * Sets the arguments for the request.
     *
     * @param array $arguments Request arguments
     *
     * @return Phergie_Event_Request Provides a fluent interface
     */
    public function setArguments($arguments)
    {
        foreach ($arguments as $argument => $value) {
            $this->setArgument($argument, $value);
        }
        return $this;
    }

    /**
     * Sets the value of a single argument for the request.
     *
     * @param mixed  $argument Integer position (starting from 0) or the
     *        equivalent string name of the argument from self::$map
     * @param string $value    Value to assign to the argument
     *
     * @return Phergie_Event_Request Provides a fluent interface
     */
    public function setArgument($argument, $value)
    {
        $argument = $this->resolveArgument($argument);
        $this->arguments[$argument] = (string) $value;
        return $this;
    }

    /**
     * Returns the arguments for the request.
     *
     * @return array
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * Removes an argument value from the request.
     *
     * @param mixed $argument Integer position (starting from 0) or the
     *        equivalent string name of the argument from self::$map
     *
     * @return Phergie_Event_Request Provides a fluent interface
     */
    public function removeArgument($argument)
    {
        $argument = $this->resolveArgument($argument);
        unset($this->arguments[$argument]);
        return $this;
    }

    /**
     * Resolves an argument specification to an integer position.
     *
     * @param mixed $argument Integer position (starting from 0) or the
     *        equivalent string name of the argument from self::$map
     *
     * @return int Integer position of the argument
     */
    protected function resolveArgument($argument)
    {
        if (isset(self::$map[$this->type])) {
            if (is_string($argument)) {
                $argument = strtolower($argument);
                if (isset(self::$map[$this->type][$argument])) {
                    return self::$map[$this->type][$argument];
                }
            } else {
                if (in_array($argument, self::$map[$this->type])) {
                    return $argument;
                }
            }
        }

        throw new Phergie_Event_Exception(
            'Argument "' . $argument . '" could not be resolved for'
                . ' event type "' . $this->type . '"',
            Phergie_Event_Exception::ERR_INVALID_ARGUMENT
        );
    }

    /**
     * Returns a single specified argument for the request.
     *
     * @param mixed $argument Integer position (starting from 0) or the
     *        equivalent string name of the argument from self::$map
     *
     * @return string|null Argument value or NULL if none is set
     */
    public function getArgument($argument)
    {
        $argument = $this->resolveArgument($argument);
        if (isset($this->arguments[$argument])) {
            return $this->arguments[$argument];
        }
        return null;
    }

    /**
     * Sets the raw buffer for the event.
     *
     * @param string $buffer Raw event buffer
     *
     * @return Phergie_Event_Request Provides a fluent interface
     */
    public function setRawData($buffer)
    {
        $this->rawData = $buffer;
        return $this;
    }

    /**
     * Returns the raw buffer sent from the server for the event.
     *
     * @return string
     */
    public function getRawData()
    {
        return $this->rawData;
    }

    /**
     * Returns the nick of the user who originated the event.
     *
     * @return string
     */
    public function getNick()
    {
        return $this->getHostmask()->getNick();
    }

    /**
     * Returns the channel name if the event occurred in a channel or the
     * user nick if the event was a private message directed at the bot by a
     * user.
     *
     * @return string
     */
    public function getSource()
    {
        if (substr($this->arguments[0], 0, 1) == '#') {
            return $this->arguments[0];
        }
        return $this->getHostmask()->getNick();
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
        $username = $this->getHostmask()->getUsername();
        return !empty($username);
    }

    /**
     * Returns whether or not the event originated from the server.
     *
     * @return TRUE if the event is from the server, FALSE otherwise
     */
    public function isFromServer()
    {
        $username = $this->getHostmask()->getUsername();
        return empty($username);
    }

    /**
     * Provides access to named parameters via virtual "getter" methods.
     *
     * @param string $name      Name of the method called
     * @param array  $arguments Arguments passed to the method (should always
     *        be empty)
     *
     * @return mixed Method return value
     */
    public function __call($name, array $arguments)
    {
        if (!count($arguments) && substr($name, 0, 3) == 'get') {
            return $this->getArgument(substr($name, 3));
        }
        throw new Phergie_Event_Exception(
            'Called invalid method ' . $name . ' in ' . __CLASS__,
            Phergie_Event_Exception::ERR_INVALID_METHOD_CALL
        );
    }

    /**
     * Checks to see if an event argument is assigned a value.
     *
     * @param string|int $offset Argument name or position beginning from 0
     *
     * @return bool TRUE if the argument has a value, FALSE otherwise
     * @see ArrayAccess::offsetExists()
     */
    public function offsetExists($offset)
    {
        try {
            return ($this->getArgument($offset) != null);
        } catch (Phergie_Event_Exception $e) {
            return false;
        }
    }

    /**
     * Returns the value of an event argument.
     *
     * @param string|int $offset Argument name or position beginning from 0
     *
     * @return string|null Argument value or NULL if none is set
     * @see ArrayAccess::offsetGet()
     */
    public function offsetGet($offset)
    {
        return $this->getArgument($offset);
    }

    /**
     * Sets the value of an event argument.
     *
     * @param string|int $offset Argument name or position beginning from 0
     * @param string     $value  New argument value
     *
     * @return void
     * @see ArrayAccess::offsetSet()
     */
    public function offsetSet($offset, $value)
    {
        $this->setArgument($offset, $value);
    }

    /**
     * Removes the value set for an event argument.
     *
     * @param string|int $offset Argument name or position beginning from 0
     *
     * @return void
     * @see ArrayAccess::offsetUnset()
     */
    public function offsetUnset($offset)
    {
        $this->removeArgument($offset);
    }
}
