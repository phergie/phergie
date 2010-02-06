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
 * Driver that uses the sockets wrapper of the streams extension for 
 * communicating with the server and handles formatting and parsing of 
 * events using PHP.
 *
 * @category Phergie 
 * @package  Phergie
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie
 */
class Phergie_Driver_Streams extends Phergie_Driver_Abstract
{
    /**
     * Socket handlers
     *
     * @var array
     */
    protected $sockets = array();

    /**
     * Reference to the currently active socket handler
     *
     * @var resource
     */
    protected $socket;

    /**
     * Handles construction of command strings and their transmission to the 
     * server.
     *
     * @param string       $command Command to send
     * @param string|array $args    Optional string or array of sequential 
     *        arguments
     *
     * @return string Command string that was sent 
     * @throws Phergie_Driver_Exception
     */
    protected function send($command, $args = '')
    {
        // Require an open socket connection to continue
        if (empty($this->socket)) {
            throw new Phergie_Driver_Exception(
                'doConnect() must be called first',
                Phergie_Driver_Exception::ERR_NO_INITIATED_CONNECTION
            );
        }

        // Add the command
        $buffer = strtoupper($command);

        // Add arguments
        if (!empty($args)) {

            // Apply formatting if arguments are passed in as an array
            if (is_array($args)) {
                $end = count($args) - 1;
                $args[$end] = ':' . $args[$end];
                $args = implode(' ', $args);
            }

            $buffer .= ' ' . $args;
        }

        // Transmit the command over the socket connection
        fwrite($this->socket, $buffer . "\r\n");

        // Return the command string that was transmitted
        return $buffer;
    }

    /**
     * Overrides the parent class to set the currently active socket handler 
     * when the active connection is changed.
     *
     * @param Phergie_Connection $connection Active connection
     *
     * @return Phergie_Driver_Abstract Provides a fluent interface
     */
    public function setConnection(Phergie_Connection $connection)
    {
        // Set the active socket handler
        $hostmask = (string) $connection->getHostmask();
        if (!empty($this->sockets[$hostmask])) {
            $this->socket = $this->sockets[$hostmask];
        }

        // Set the active connection
        return parent::setConnection($connection);
    }

    /**
     * Supporting method to parse event argument strings where the last 
     * argument may contain a colon.
     *
     * @param string $args  Argument string to parse
     * @param int    $count Optional maximum number of arguments
     *
     * @return array Array of argument values
     */
    protected function parseArguments($args, $count = -1)
    {
        return preg_split('/ :?/S', $args, $count);
    }

    /**
     * Listens for an event on the current connection.
     *
     * @return Phergie_Event_Interface|null Event instance if an event was 
     *         received, NULL otherwise
     */
    public function getEvent()
    {
        // Check for a new event on the current connection
        $buffer = fgets($this->socket, 512);

        // If no new event was found, return NULL
        if (empty($buffer)) {
            return null;
        }

        // Strip the trailing newline from the buffer
        $buffer = rtrim($buffer);

        // If the event is from the server...
        if (substr($buffer, 0, 1) != ':') {

            // Parse the command and arguments
            list($cmd, $args) = array_pad(explode(' ', $buffer, 2), 2, null);

        } else {
            // If the event could be from the server or a user...

            // Parse the server hostname or user hostmask, command, and arguments
            list($prefix, $cmd, $args) 
                = array_pad(explode(' ', ltrim($buffer, ':'), 3), 3, null);
            if (strpos($prefix, '@') !== false) {
                $hostmask = Phergie_Hostmask::fromString($prefix);
            }
        }

        // Parse the event arguments depending on the event type
        $cmd = strtolower($cmd);
        switch ($cmd) {
        case 'names':
        case 'nick':
        case 'quit':
        case 'ping':
        case 'join':
        case 'error':
            $args = array(ltrim($args, ':'));
            break;

        case 'privmsg':
        case 'notice':
            $ctcp = substr(strstr($args, ':'), 1);
            if (substr($ctcp, 0, 1) === "\x01" && substr($ctcp, -1) === "\x01") {
                $ctcp = substr($ctcp, 1, -1);
                $reply = ($cmd == 'notice');
                list($cmd, $args) = array_pad(explode(' ', $ctcp, 2), 2, null);
                $cmd = strtolower($cmd);
                switch ($cmd) {
                case 'version':
                case 'time':
                case 'finger':
                    if ($reply) {
                        $args = $ctcp;
                    }
                case 'ping':
                    if ($reply) {
                        $cmd .= 'Response';
                    }
                case 'action':
                    $args = array($this->getConnection()->getNick(), $args);
                    break;

                default:
                    $cmd = 'ctcp';
                    if ($reply) {
                        $cmd .= 'Response';
                    }
                    $args = array($this->getConnection()->getNick(), $ctcp);
                    break;
                }
            } else {
                $args = $this->parseArguments($args, 2);
            }
            break;

        case 'oper':
        case 'topic':
        case 'mode':
            $args = $this->parseArguments($args); 
            break;

        case 'part':
        case 'kill':
        case 'invite':
            $args = $this->parseArguments($args, 2); 
            break;

        case 'kick':
            $args = $this->parseArguments($args, 3); 
            break;

        // Remove the target from responses
        default:
            $args = substr($args, strpos($args, ' ') + 1);
            break;
        }

        // Create, populate, and return an event object
        if (ctype_digit($cmd)) {
            $event = new Phergie_Event_Response;
            $event
                ->setCode($cmd)
                ->setDescription($args);
        } else {
            $event = new Phergie_Event_Request;
            $event
                ->setType($cmd)
                ->setArguments($args);
            if (isset($hostmask)) {
                $event->setHostmask($hostmask);
            }
        }
        $event->setRawData($buffer);
        return $event;
    }

    /**
     * Initiates a connection with the server.
     *
     * @return void
     */
    public function doConnect()
    {
        // Listen for input indefinitely
        set_time_limit(0);

        // Get connection information
        $connection = $this->getConnection();
        $hostname = $connection->getHost();
        $port = $connection->getPort();
        $password = $connection->getPassword();
        $username = $connection->getUsername();
        $nick = $connection->getNick();
        $realname = $connection->getRealname();

        // Establish and configure the socket connection
        $remote = 'tcp://' . $hostname . ':' . $port;
        $this->socket = @stream_socket_client($remote, $errno, $errstr);
        if (!$this->socket) {
            throw new Phergie_Driver_Exception(
                'Unable to connect: socket error ' . $errno . ' ' . $errstr,
                Phergie_Driver_Exception::ERR_CONNECTION_ATTEMPT_FAILED
            );
        }

        // Send the password if one is specified
        if (!empty($password)) {
            $this->send('PASS', $password);
        }

        // Send user information
        $this->send(
            'USER',
            array(
                $username, 
                $hostname, 
                $hostname, 
                $realname
            )
        );

        $this->send('NICK', $nick); 

        // Add the socket handler to the internal array for socket handlers
        $this->sockets[(string) $connection->getHostmask()] = $this->socket;
    }

    /**
     * Terminates the connection with the server.
     *
     * @param string $reason Reason for connection termination (optional)
     *
     * @return void
     */
    public function doQuit($reason = null)
    {
        // Send a QUIT command to the server
        $this->send('QUIT', $reason);

        // Terminate the socket connection
        fclose($this->socket);

        // Remove the socket from the internal socket list
        unset($this->sockets[(string) $this->getConnection()->getHostmask()]);
    }

    /**
     * Joins a channel.
     *
     * @param string $channels Comma-delimited list of channels to join 
     * @param string $keys     Optional comma-delimited list of channel keys
     *
     * @return void
     */
    public function doJoin($channels, $keys = null)
    {
        $args = array($channels);

        if (!empty($keys)) {
            $args[] = $keys;
        }

        $this->send('JOIN', $args);
    }

    /**
     * Leaves a channel.
     *
     * @param string $channels Comma-delimited list of channels to leave 
     *
     * @return void
     */
    public function doPart($channels)
    {
        $this->send('PART', $channels);
    }

    /**
     * Invites a user to an invite-only channel.
     *
     * @param string $nick    Nick of the user to invite
     * @param string $channel Name of the channel
     *
     * @return void
     */
    public function doInvite($nick, $channel)
    {
        $this->send('INVITE', array($nick, $channel));
    }

    /**
     * Obtains a list of nicks of usrs in currently joined channels.
     *
     * @param string $channels Comma-delimited list of one or more channels
     *
     * @return void
     */
    public function doNames($channels)
    {
        $this->send('NAMES', $channels);
    }

    /**
     * Obtains a list of channel names and topics.
     *
     * @param string $channels Comma-delimited list of one or more channels
     *                         to which the response should be restricted
     *                         (optional)
     *
     * @return void
     */
    public function doList($channels = null)
    {
        $this->send('LIST', $channels);
    }

    /**
     * Retrieves or changes a channel topic.
     *
     * @param string $channel Name of the channel
     * @param string $topic   New topic to assign (optional)
     *
     * @return void
     */
    public function doTopic($channel, $topic = null)
    {
        $args = array($channel);

        if (!empty($topic)) {
            $args[] = $topic;
        }

        $this->send('TOPIC', $args);
    }

    /**
     * Retrieves or changes a channel or user mode.
     *
     * @param string $target Channel name or user nick
     * @param string $mode   New mode to assign (optional)
     *
     * @return void
     */
    public function doMode($target, $mode = null)
    {
        $args = array($target);

        if (!empty($mode)) {
            $args[] = $mode;
        }

        $this->send('MODE', $args);
    }

    /**
     * Changes the client nick.
     *
     * @param string $nick New nick to assign
     *
     * @return void
     */
    public function doNick($nick)
    {
        $this->send('NICK', $nick);
    }

    /**
     * Retrieves information about a nick.
     *
     * @param string $nick Nick
     *
     * @return void
     */
    public function doWhois($nick)
    {
        $this->send('WHOIS', $nick);
    }

    /**
     * Sends a message to a nick or channel.
     *
     * @param string $target Channel name or user nick
     * @param string $text   Text of the message to send
     *
     * @return void
     */
    public function doPrivmsg($target, $text)
    {
        $this->send('PRIVMSG', array($target, $text));
    }

    /**
     * Sends a notice to a nick or channel.
     *
     * @param string $target Channel name or user nick
     * @param string $text   Text of the notice to send
     *
     * @return void
     */
    public function doNotice($target, $text)
    {
        $this->send('NOTICE', array($target, $text));
    }

    /**
     * Kicks a user from a channel.
     *
     * @param string $nick    Nick of the user
     * @param string $channel Channel name
     * @param string $reason  Reason for the kick (optional)
     *
     * @return void
     */
    public function doKick($nick, $channel, $reason = null)
    {
        $args = array($nick, $channel);

        if (!empty($reason)) {
            $args[] = $response;
        }

        $this->send('KICK', $args);
    }

    /**
     * Responds to a server test of client responsiveness.
     *
     * @param string $daemon Daemon from which the original request originates
     *
     * @return void
     */
    public function doPong($daemon)
    {
        $this->send('PONG', $daemon);
    }

    /**
     * Sends a CTCP ACTION (/me) command to a nick or channel.
     *
     * @param string $target Channel name or user nick
     * @param string $text   Text of the action to perform
     *
     * @return void
     */
    public function doAction($target, $text)
    {
        $buffer = rtrim('ACTION ' . $text);

        $this->doPrivmsg($target, chr(1) . $buffer . chr(1));
    }

    /**
     * Sends a CTCP response to a user.
     *
     * @param string       $nick    User nick 
     * @param string       $command Command to send
     * @param string|array $args    String or array of sequential arguments 
     *        (optional)
     *
     * @return void
     */
    protected function doCtcp($nick, $command, $args = null)
    {
        if (is_array($args)) {
            $args = implode(' ', $args);
        }

        $buffer = rtrim(strtoupper($command) . ' ' . $args);

        $this->doNotice($nick, chr(1) . $buffer . chr(1)); 
    }

    /**
     * Sends a CTCP PING request or response (they are identical) to a user.
     *
     * @param string $nick User nick
     * @param string $hash Hash to use in the handshake
     *
     * @return void
     */
    public function doPing($nick, $hash)
    {
        $this->doCtcp($nick, 'PING', $hash);
    }

    /**
     * Sends a CTCP VERSION request to a user.
     *
     * @param string $nick User nick
     *
     * @return void
     */
    public function doVersion($nick)
    {
        $this->doCtcp($nick, 'VERSION');
    }

    /**
     * Sends a CTCP VERSION response to a user.
     *
     * @param string $nick    User nick
     * @param string $version Version string to send
     *
     * @return void
     */
    public function doVersionResponse($nick, $version)
    {
        $this->doCtcp($nick, 'VERSION', $version);
    }

    /**
     * Sends a CTCP TIME request to a user.
     *
     * @param string $nick User nick
     *
     * @return void
     */
    public function doTime($nick)
    {
        $this->doCtcp($nick, 'TIME');
    }

    /**
     * Sends a CTCP TIME response to a user.
     *
     * @param string $nick User nick
     * @param string $time Time string to send
     *
     * @return void
     */
    public function doTimeResponse($nick, $time)
    {
        $this->doCtcp($nick, 'TIME', $time);
    }

    /**
     * Sends a CTCP FINGER request to a user.
     *
     * @param string $nick User nick
     *
     * @return void
     */
    public function doFinger($nick)
    {
        $this->doCtcp($nick, 'FINGER');
    }

    /**
     * Sends a CTCP FINGER response to a user.
     *
     * @param string $nick   User nick
     * @param string $finger Finger string to send
     *
     * @return void
     */
    public function doFingerResponse($nick, $finger)
    {
        $this->doCtcp($nick, 'FINGER', $finer);
    }

    /**
     * Sends a raw command to the server.
     *
     * @param string $command Command string to send
     *
     * @return void
     */
    public function doRaw($command)
    {
        $this->send('RAW', $command);
    }
}
