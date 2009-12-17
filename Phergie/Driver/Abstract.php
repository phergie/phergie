<?php

/**
 * Base class for drivers which handle issuing client commands to the IRC
 * server and converting responses into usable data objects.
 */
abstract class Phergie_Driver_Abstract
{
    /**
     * Currently active connection
     *
     * @var Phergie_Connection
     */
    protected $_connection;

    /**
     * Sets the currently active connection.
     *
     * @param Phergie_Connection $connection
     * @return Phergie_Driver_Abstract Provides a fluent interface
     */
    public function setConnection(Phergie_Connection $connection)
    {
        $this->_connection = $connection;

        return $this;
    }

    /**
     * Returns the currently active connection.
     *
     * @return Phergie_Connection
     * @throws Phergie_Driver_Exception
     */
    public function getConnection()
    {
        if (empty($this->_connection)) {
            throw new Phergie_Driver_Exception(
                'Operation requires an active connection, but none is set',
                Phergie_Driver_Exception::ERR_NO_ACTIVE_CONNECTION
            );
        }

        return $this->_connection;
    }

    /**
     * Returns an event if one has been received from the server.
     *
     * @return Phergie_Event_Interface|null Event instance if an event has
     *         been received, NULL otherwise
     */
    public abstract function getEvent();

    /**
     * Initiates a connection with the server.
     *
     * @return void
     */
    public abstract function doConnect();

    /**
     * Terminates the connection with the server.
     *
     * @param string $reason Reason for connection termination (optional)
     * @return void
     */
    public abstract function doQuit($reason = null);

    /**
     * Joins a channel.
     *
     * @param string $channels Comma-delimited list of channels to join 
     * @param string $keys Optional comma-delimited list of channel keys
     * @return void
     */
    public abstract function doJoin($channels, $key = null);

    /**
     * Leaves a channel.
     *
     * @param string $channels Comma-delimited list of channels to leave 
     * @return void
     */
    public abstract function doPart($channels);

    /**
     * Invites a user to an invite-only channel.
     *
     * @param string $nick Nick of the user to invite
     * @param string $channel Name of the channel
     * @return void
     */
    public abstract function doInvite($nick, $channel);

    /**
     * Obtains a list of nicks of users in specified channels.
     *
     * @param string $channels Comma-delimited list of one or more channels
     * @return void
     */
    public abstract function doNames($channels);

    /**
     * Obtains a list of channel names and topics.
     *
     * @param string $channels Comma-delimited list of one or more channels
     *                         to which the response should be restricted
     *                         (optional)
     * @return void
     */
    public abstract function doList($channels = null);

    /**
     * Retrieves or changes a channel topic.
     *
     * @param string $channel Name of the channel
     * @param string $topic New topic to assign (optional)
     * @return void
     */
    public abstract function doTopic($channel, $topic = null);

    /**
     * Retrieves or changes a channel or user mode.
     *
     * @param string $target Channel name or user nick
     * @param string $mode New mode to assign (optional)
     * @return void
     */
    public abstract function doMode($target, $mode = null);

    /**
     * Changes the client nick.
     *
     * @param string $nick New nick to assign
     * @return void
     */
    public abstract function doNick($nick);

    /**
     * Retrieves information about a nick.
     *
     * @param string $nick
     * @return void
     */
    public abstract function doWhois($nick);

    /**
     * Sends a message to a nick or channel.
     *
     * @param string $target Channel name or user nick
     * @param string $text Text of the message to send
     * @return void
     */
    public abstract function doPrivmsg($target, $text);

    /**
     * Sends a notice to a nick or channel.
     *
     * @param string $target Channel name or user nick
     * @param string $text Text of the notice to send
     * @return void
     */
    public abstract function doNotice($target, $text);

    /**
     * Kicks a user from a channel.
     *
     * @param string $nick Nick of the user
     * @param string $channel Channel name
     * @param string $reason Reason for the kick (optional)
     * @return void
     */
    public abstract function doKick($nick, $channel, $reason = null);

    /**
     * Responds to a server test of client responsiveness.
     *
     * @param string $daemon Daemon from which the original request originates
     * @return void
     */
    public abstract function doPong($daemon);

    /**
     * Sends a CTCP ACTION (/me) command to a nick or channel.
     *
     * @param string $target Channel name or user nick
     * @param string $text Text of the action to perform
     * @return void
     */
    public abstract function doAction($target, $text);

    /**
     * Sends a CTCP PING response to a user.
     *
     * @param string $nick User nick
     * @param string $hash PING hash to use in the handshake
     * @return void
     */
    public abstract function doPing($nick, $hash);

    /**
     * Sends a CTCP VERSION response to a user.
     *
     * @param string $nick User nick
     * @param string $version Version string to send
     * @return void
     */
    public abstract function doVersion($nick, $version);

    /**
     * Sends a CTCP TIME response to a user.
     *
     * @param string $user User nick
     * @param string $time Time string to send
     * @return void
     */
    public abstract function doTime($nick, $time);

    /**
     * Sends a raw command to the server.
     *
     * @param string $command Command string to send
     * @return void
     */
    public abstract function doRaw($command);
}
