<?php

/**
 * Driver that uses the sockets wrapper of the streams extension for 
 * communicating with the server and handles formatting and parsing of 
 * events using PHP.
 */
class Phergie_Driver_Streams extends Phergie_Driver_Abstract
{
    /**
     * Socket handlers
     *
     * @var array
     */
    protected $_sockets = array();

    /**
     * Reference to the currently active socket handler
     *
     * @var resource
     */
    protected $_socket;

    /**
     * Handles construction of command strings and their transmission to the 
     * server.
     *
     * @param string $command Command to send
     * @param string|array $args Optional string or array of sequential 
     *        arguments
     * @return string Command string that was sent 
     * @throws Phergie_Driver_Exception
     */
    protected function _send($command, $args = '')
    {
        // Require an open socket connection to continue
        if (empty($this->_socket)) {
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
        fwrite($this->_socket, $buffer . "\r\n");

        // Return the command string that was transmitted
        return $buffer;
    }

    /**
     * Overrides the parent class to set the currently active socket handler 
     * when the active connection is changed.
     *
     * @param Phergie_Connection $connection
     * @return Phergie_Driver_Abstract Provides a fluent interface
     */
    public function setConnection(Phergie_Connection $connection)
    {
        // Set the active socket handler
        $hostmask = (string) $connection->getHostmask();
        if (!empty($this->_sockets[$hostmask])) {
            $this->_socket = $this->_sockets[$hostmask];
        }

        // Set the active connection
        return parent::setConnection($connection);
    }

    /**
     * Supporting method to parse event argument strings where the last 
     * argument may contain a colon.
     *
     * @param string $args Argument string to parse
     * @param int $count Optional maximum number of arguments
     * @return array Array of argument values
     */
    protected function _parseArguments($args, $count = -1)
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
        $buffer = fgets($this->_socket, 512);

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

        // If the event could be from the server or a user...
        } else {

            // Parse the server hostname or user hostmask, command, and arguments
            list($prefix, $cmd, $args) = array_pad(explode(' ', ltrim($buffer, ':'), 3), 3, null);
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
                            if ($reply) {
                                $args = $ctcp;
                            }
                        case 'ping':
                            if ($reply) {
                                $cmd .= 'Reply';
                            }
                        case 'action':
                            $args = array($this->getConnection()->getNick(), $args);
                        break;

                        default:
                            $cmd = 'ctcp';
                            if ($reply) {
                                $cmd .= 'Reply';
                            }
                            $args = array($this->getConnection()->getNick(), $ctcp);
                        break;
                    }
                } else {
                    $args = $this->_parseArguments($args, 2);
                }
            break;

            case 'oper':
            case 'topic':
            case 'mode':
                $args = $this->_parseArguments($args); 
            break;

            case 'part':
            case 'kill':
            case 'invite':
                $args = $this->_parseArguments($args, 2); 
            break;

            case 'kick':
                $args = $this->_parseArguments($args, 3); 
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
        $this->_socket = @stream_socket_client($remote, $errno, $errstr);
        if (!$this->_socket) {
            throw new Phergie_Driver_Exception(
                'Unable to connect to server: socket error ' . $errno . ' ' . $errstr,
                Phergie_Driver_Exception::ERR_CONNECTION_ATTEMPT_FAILED
            );
        }

        // Send the password if one is specified
        if (!empty($password)) {
            $this->_send('PASS', $password);
        }

        // Send user information
        $this->_send('USER', array(
            $username, 
            $hostname, 
            $hostname, 
            $realname
        ));

        $this->_send('NICK', $nick); 

        // Add the socket handler to the internal array for socket handlers
        $this->_sockets[(string) $connection->getHostmask()] = $this->_socket;
    }

    /**
     * Terminates the connection with the server.
     *
     * @param string $reason Reason for connection termination (optional)
     * @return void
     */
    public function doQuit($reason = null)
    {
        // Send a QUIT command to the server
        $this->_send('QUIT', $reason);

        // Terminate the socket connection
        fclose($this->_socket);

        // Remove the socket from the internal socket list
        unset($this->_sockets[(string) $this->getConnection()->getHostmask()]);
    }

    /**
     * Joins a channel.
     *
     * @param string $channels Comma-delimited list of channels to join 
     * @param string $keys Optional comma-delimited list of channel keys
     * @return void
     */
    public function doJoin($channel, $key = null)
    {
        $args = array($channel);

        if (!empty($key)) {
            $args[] = $key;
        }

        $this->_send('JOIN', $args);
    }

    /**
     * Leaves a channel.
     *
     * @param string $channels Comma-delimited list of channels to leave 
     * @return void
     */
    public function doPart($channel, $reason = null)
    {
        $args = array($channel);

        if (!empty($reason)) {
            $args[] = $reason;
        }

        $this->_send('PART', $args);
    }

    /**
     * Invites a user to an invite-only channel.
     *
     * @param string $nick Nick of the user to invite
     * @param string $channel Name of the channel
     * @return void
     */
    public function doInvite($nick, $channel)
    {
        $this->_send('INVITE', array($nick, $channel));
    }

    /**
     * Obtains a list of nicks of usrs in currently joined channels.
     *
     * @param string $channels Comma-delimited list of one or more channels
     * @return void
     */
    public function doNames($channels)
    {
        $this->_send('NAMES', $channels);
    }

    /**
     * Obtains a list of channel names and topics.
     *
     * @param string $channels Comma-delimited list of one or more channels
     *                         to which the response should be restricted
     *                         (optional)
     * @return void
     */
    public function doList($channels = null)
    {
        $this->_send('LIST', $channels);
    }

    /**
     * Retrieves or changes a channel topic.
     *
     * @param string $channel Name of the channel
     * @param string $topic New topic to assign (optional)
     * @return void
     */
    public function doTopic($channel, $topic = null)
    {
        $args = array($channel);

        if (!empty($topic)) {
            $args[] = $topic;
        }

        $this->_send('TOPIC', $args);
    }

    /**
     * Retrieves or changes a channel or user mode.
     *
     * @param string $target Channel name or user nick
     * @param string $mode New mode to assign (optional)
     * @return void
     */
    public function doMode($target, $mode = null)
    {
        $args = array($target);

        if (!empty($mode)) {
            $args[] = $mode;
        }

        $this->_send('MODE', $args);
    }

    /**
     * Changes the client nick.
     *
     * @param string $nick New nick to assign
     * @return void
     */
    public function doNick($nick)
    {
        $this->_send('NICK', $nick);
    }

    /**
     * Retrieves information about a nick.
     *
     * @param string $nick
     * @return void
     */
    public function doWhois($nick)
    {
        $this->_send('WHOIS', $nick);
    }

    /**
     * Sends a message to a nick or channel.
     *
     * @param string $target Channel name or user nick
     * @param string $text Text of the message to send
     * @return void
     */
    public function doPrivmsg($target, $text)
    {
        $this->_send('PRIVMSG', array($target, $text));
    }

    /**
     * Sends a notice to a nick or channel.
     *
     * @param string $target Channel name or user nick
     * @param string $text Text of the notice to send
     * @return void
     */
    public function doNotice($target, $text)
    {
        $this->_send('NOTICE', array($target, $text));
    }

    /**
     * Kicks a user from a channel.
     *
     * @param string $nick Nick of the user
     * @param string $channel Channel name
     * @param string $reason Reason for the kick (optional)
     * @return void
     */
    public function doKick($nick, $channel, $reason = null)
    {
        $args = array($nick, $channel);

        if (!empty($reason)) {
            $args[] = $response;
        }

        $this->_send('KICK', $args);
    }

    /**
     * Responds to a server test of client responsiveness.
     *
     * @param string $daemon Daemon from which the original request originates
     * @return void
     */
    public function doPong($daemon)
    {
        $this->_send('PONG', $daemon);
    }

    /**
     * Sends a CTCP ACTION (/me) command to a nick or channel.
     *
     * @param string $target Channel name or user nick
     * @param string $text Text of the action to perform
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
     * @param string $nick User nick 
     * @param string $command Command to send
     * @param string|array $args String or array of sequential arguments 
     *        (optional)
     * @return void
     */
    protected function _doCtcpResponse($nick, $command, $args = null)
    {
        if (is_array($args)) {
            $args = implode(' ', $args);
        }

        $buffer = rtrim(strtoupper($command) . ' ' . $args);

        $this->doNotice($nick, chr(1) . $buffer . chr(1)); 
    }

    /**
     * Sends a CTCP PING response to a user.
     *
     * @param string $nick User nick
     * @param string $hash PING hash to use in the handshake
     * @return void
     */
    public function doPing($nick, $hash)
    {
        $this->_doCtcpResponse($nick, 'PING', $hash);
    }

    /**
     * Sends a CTCP VERSION response to a user.
     *
     * @param string $nick User nick
     * @param string $version Version string to send
     * @return void
     */
    public function doVersion($nick, $version)
    {
        $this->_doCtcpResponse($nick, 'VERSION', $version);
    }

    /**
     * Sends a CTCP TIME response to a user.
     *
     * @param string $user User nick
     * @param string $time Time string to send
     * @return void
     */
    public function doTime($nick, $time)
    {
        $this->_doCtcpResponse($nick, 'TIME', $time);
    }

    /**
     * Sends a raw command to the server.
     *
     * @param string $command Command string to send
     * @return void
     */
    public function doRaw($command)
    {
        $this->_send('RAW', $command);
    }
}
