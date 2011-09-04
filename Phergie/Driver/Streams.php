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
 * @copyright 2008-2011 Phergie Development Team (http://phergie.org)
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
     * Amount of time in seconds to wait to receive an event each time the
     * socket is polled
     *
     * @var float
     */
    protected $timeout = 0.1;

    /**
     * Writes data to the socket, separatedly mainly to allow for stubbing
     * during unit testing.
     *
     * @param string $data Data to write to the socket
     *
     * @return int Number of bytes successfully written to the socket
     */
    protected function write($data)
    {
        // @codeCoverageIgnoreStart
        return (int) fwrite($this->socket, $data);
        // @codeCoverageIgnoreEnd
    }

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
        $connection = $this->getConnection();
        $encoding = $connection->getEncoding();

        // Add the command
        $buffer = $command;

        // Add arguments
        if (!empty($args)) {

            // Apply formatting if arguments are passed in as an array
            if (is_array($args)) {
                $end = count($args) - 1;
                $args[$end] = ':' . $args[$end];
                $args = implode(' ', $args);
            } else {
                $args = ':' . $args;
            }

            $buffer .= ' ' . preg_replace('/\v+/', ' ', $args);
        }

        // Transmit the command over the socket connection
        $attempts = $written = 0;
        $temp = $buffer . "\r\n";
        $is_multibyte = !substr($encoding, 0, 8) === 'ISO-8859'
                     && $encoding !== 'ASCII'
                     && $encoding !== 'CP1252';
        $length = ($is_multibyte) ? mb_strlen($buffer, '8bit') : strlen($buffer);
        while (true) {
            $written += $this->write($temp);
            if ($written < $length) {
                $temp = substr($temp, $written);
                $attempts++;
                if ($attempts == 3) {
                    throw new Phergie_Driver_Exception(
                        'Unable to write to socket',
                        Phergie_Driver_Exception::ERR_CONNECTION_WRITE_FAILED
                    );
                }
            } else {
                break;
            }
        }

        // Return the command string that was transmitted
        return $buffer;
    }

    /**
     * Overrides the parent class to set the currently active socket handler
     * when the active connection is changed.
     *
     * @param Phergie_Connection $connection Active connection
     *
     * @return Phergie_Driver_Streams Provides a fluent interface
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
     * Returns a list of hostmasks corresponding to sockets with data to read.
     *
     * @param int $sec  Length of time to wait for new data (seconds)
     * @param int $usec Length of time to wait for new data (microseconds)
     *
     * @return array List of hostmasks or an empty array if none were found
     *         to have data to read
     */
    public function getActiveReadSockets($sec = 0, $usec = 200000)
    {
        $read = $this->sockets;
        $write = $except = null;
        $active = array();

        if (count($this->sockets) > 0) {
            $number = stream_select($read, $write, $except, $sec, $usec);
            if ($number > 0) {
                foreach ($read as $item) {
                    $active[] = array_search($item, $this->sockets);
                }
            }
        }

        return $active;
    }

    /**
     * Sets the amount of time to wait for a new event each time the socket
     * is polled.
     *
     * @param float $timeout Amount of time in seconds
     *
     * @return Phergie_Driver_Streams Provides a fluent interface
     */
    public function setTimeout($timeout)
    {
        $timeout = (float) $timeout;
        if ($timeout) {
            $this->timeout = $timeout;
        }
        return $this;
    }

    /**
     * Returns the amount of time to wait for a new event each time the
     * socket is polled.
     *
     * @return float Amount of time in seconds
     */
    public function getTimeout()
    {
        return $this->timeout;
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
        $buffer = '';
        do {
            $buffer .= fgets($this->socket, 512);
        } while (!empty($buffer) && !preg_match('/\v+$/', $buffer));
        $buffer = trim($buffer);

        // If no new event was found, return NULL
        if (empty($buffer)) {
            return null;
        }

        // If the event has a prefix, extract it
        $prefix = '';
        if (substr($buffer, 0, 1) == ':') {
            $parts = explode(' ', $buffer, 3);
            $prefix = substr(array_shift($parts), 1);
            $buffer = implode(' ', $parts);
        }

        // Parse the command and arguments
        list($cmd, $args) = array_pad(explode(' ', $buffer, 2), 2, null);

        // Parse the server name or hostmask
        if (strpos($prefix, '@') === false) {
            $hostmask = new Phergie_Hostmask(
                null, null, $prefix
            );
        } else {
            $hostmask = Phergie_Hostmask::fromString($prefix);
        }

        // Parse the event arguments depending on the event type
        $cmd = strtolower($cmd);
        switch ($cmd) {
        case 'names':
        case 'nick':
        case 'quit':
        case 'ping':
        case 'pong':
        case 'error':
            $args = array_filter(array(ltrim($args, ':')));
            break;

        case 'privmsg':
        case 'notice':
            $args = $this->parseArguments($args, 2);
            list($source, $ctcp) = $args;
            if (substr($ctcp, 0, 1) === "\001" && substr($ctcp, -1) === "\001") {
                $ctcp = substr($ctcp, 1, -1);
                $reply = ($cmd == 'notice');
                list($cmd, $args) = array_pad(explode(' ', $ctcp, 2), 2, array());
                $cmd = strtolower($cmd);
                switch ($cmd) {
                case 'version':
                case 'time':
                case 'finger':
                case 'ping':
                    if ($reply) {
                        $args = array($args);
                    }
                    break;
                case 'action':
                    $args = array($source, $args);
                    break;
                }
            }
            // This fixes the issue that seems to occur, but why does it?
            if (!is_array($args)) {
                  $args = array($args);
            }
            break;

        case 'topic':
        case 'part':
        case 'invite':
        case 'join':
            $args = $this->parseArguments($args, 2);
            break;

        case 'kick':
        case 'mode':
            $args = $this->parseArguments($args, 3);
            break;

        // Remove target and colon preceding description from responses
        default:
            $args = substr($args, strpos($args, ' ') + 2);
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
            $event->setHostmask($hostmask);
        }
        $event->setRawData($buffer);
        return $event;
    }

    /**
     * Establishes a socket connection, separatedly mainly to allow for
     * stubbing during unit testing.
     *
     * @param string $remote  Address to connect the socket to
     * @param int    &$errno  System level error number if connection fails
     * @param string &$errstr System level error message if connection fails
     *
     * @return resource Established socket
     */
    protected function connect($remote, &$errno, &$errstr)
    {
        // @codeCoverageIgnoreStart
        return @stream_socket_client($remote, $errno, $errstr);
        // @codeCoverageIgnoreEnd
    }

    /**
     * Initiates a connection with the server.
     *
     * @return void
     */
    public function doConnect()
    {
        // Get connection information
        $connection = $this->getConnection();
        $hostname = $connection->getHost();
        $port = $connection->getPort();
        $password = $connection->getPassword();
        $username = $connection->getUsername();
        $nick = $connection->getNick();
        $realname = $connection->getRealname();
        $transport = $connection->getTransport();

        // Establish and configure the socket connection
        $remote = $transport . '://' . $hostname . ':' . $port;
        $errno = $errstr = null;
        $this->socket = $this->connect($remote, $errno, $errstr);
        if (!$this->socket) {
            throw new Phergie_Driver_Exception(
                'Unable to connect: socket error ' . $errno . ' ' . $errstr,
                Phergie_Driver_Exception::ERR_CONNECTION_ATTEMPT_FAILED
            );
        }

        $seconds = (int) $this->timeout;
        $microseconds = ($this->timeout - $seconds) * 1000000;
        stream_set_timeout($this->socket, $seconds, $microseconds);

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
     * @param string $param  User limit when $mode is 'l', user hostmask
     *        when $mode is 'b', or user nick when $mode is 'o'
     *
     * @return void
     */
    public function doMode($target, $mode = null, $param = null)
    {
        $args = array($target);

        if (!empty($mode)) {
            $args[] = $mode;
        }

        if (!empty($param)) {
            $args[] = $param;
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
            $args[] = $reason;
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
     * @param string $nick    User nick
     * @param string $command Command to send
     * @param string $args    Sequential arguments (optional)
     *
     * @return void
     */
    protected function doCtcp($nick, $command, $args = null)
    {
        $buffer = rtrim(strtoupper($command) . ' ' . $args);

        $this->doPrivmsg($nick, chr(1) . $buffer . chr(1));
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
     * Sends a CTCP VERSION request or response to a user.
     *
     * @param string $nick    User nick
     * @param string $version Version string to send for a response
     *
     * @return void
     */
    public function doVersion($nick, $version = null)
    {
        if ($version) {
            $this->doCtcp($nick, 'VERSION', $version);
        } else {
            $this->doCtcp($nick, 'VERSION');
        }
    }

    /**
     * Sends a CTCP TIME request to a user.
     *
     * @param string $nick User nick
     * @param string $time Time string to send for a response
     *
     * @return void
     */
    public function doTime($nick, $time = null)
    {
        if ($time) {
            $this->doCtcp($nick, 'TIME', $time);
        } else {
            $this->doCtcp($nick, 'TIME');
        }
    }

    /**
     * Sends a CTCP FINGER request to a user.
     *
     * @param string $nick   User nick
     * @param string $finger Finger string to send for a response
     *
     * @return void
     */
    public function doFinger($nick, $finger = null)
    {
        if ($finger) {
            $this->doCtcp($nick, 'FINGER', $finger);
        } else {
            $this->doCtcp($nick, 'FINGER');
        }
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
        $this->send($command);
    }
}

