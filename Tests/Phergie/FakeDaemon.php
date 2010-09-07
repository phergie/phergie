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
 * @package   Phergie_Tests
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2010 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Tests
 */

/**
 * Simulates a daemon process for testing client components.
 *
 * @category Phergie
 * @package  Phergie_Tests
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Tests
 */
class Phergie_FakeDaemon
{
    /**
     * Port on which the daemon will listen
     *
     * @var int
     */
    protected $port;

    /**
     * Socket transport for the daemon to use
     *
     * @var string
     */
    protected $transport;

    /**
     * Enumerated array containing strings of PHP code to execute when the
     * daemon is run
     *
     * @var array
     */
    protected $commands;

    /**
     * Handle for the daemon process
     *
     * @var resource
     */
    protected $process;

    /**
     * Pipes for the daemon process
     *
     * @var array
     */
    protected $pipes;

    /**
     * Input data received from the client
     *
     * @var array
     */
    protected $input;

    /**
     * Constructor to initialize instance properties.
     *
     * @param int    $port      Port on which the daemon will listen or NULL
     *        to select a port automatically, defaults to NULL
     * @param string $transport Socket transport for the daemon to use,
     *        defaults to 'tcp'
     *
     * @return void
     */
    public function __construct($port = null, $transport = 'tcp')
    {
        if (in_array($transport, stream_get_transports())) {
            $this->transport = $transport;
        } else {
            $this->transport = 'tcp';
        }

        $this->port = $port ? $port : $this->findPort();

        $this->commands = array();
        $this->input = array();
    }

    /**
     * Error handler for the port scanner.
     *
     * @param int $errno  Error number
     * @param int $errstr Error message
     *
     * @return bool TRUE to allow the process the continue
     */
    protected function handleError($errno, $errstr)
    {
        return true;
    }

    /**
     * Locates an accessible unused port.
     *
     * @return int Port
     */
    protected function findPort()
    {
        $port = 1023;

        set_error_handler(array($this, 'handleError'));
        do {
            $port++;
            $stream = stream_socket_client(
                $this->transport . '://0.0.0.0:' . $port
            );
        } while ($stream !== false);
        restore_error_handler();

        return $port;
    }

    /**
     * Returns the port in use by the daemon.
     *
     * @return int Port
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * Returns the transport in use by the daemon.
     *
     * @return string Transport
     */
    public function getTransport()
    {
        return $this->transport;
    }

    /**
     * Adds an instruction for the daemon to receive input from the client.
     *
     * @return Phergie_FakeDaemon Provides a fluent interface
     */
    public function get()
    {
        $this->commands[] = '$input .= stream_get_contents($client);';
        return $this;
    }

    /**
     * Adds an instruction for the daemon to serve the contents of a
     * specified string verbatim to the client.
     *
     * @param string $string String containing the data to serve
     *
     * @return Phergie_FakeDaemon Provides a fluent interface
     */
    public function put($string)
    {
        $string = var_export($string, true);
        $this->commands[] =
            'fwrite($client, ' . $path . ');';
        return $this;
    }

    /**
     * Adds an instruction for the daemon to serve the contents of a
     * specified file verbatim to the client.
     *
     * @param string $path Path to the file to serve
     *
     * @return Phergie_FakeDaemon Provides a fluent interface
     */
    public function putFile($path)
    {
        $path = var_export($path, true);
        $this->commands[] =
            'fwrite($client, file_get_contents(' . $path . '));';
        return $this;
    }

    /**
     * Spawns the daemon process.
     *
     * @return Phergie_FakeDaemon Provides a fluent interface
     */
    public function run()
    {
        $code = '<?php $input = \'\';'
              . '$server = stream_socket_server(\''
              . $this->transport . '://0.0.0.0:' . $this->port
              . '\');' . PHP_EOL
              . '$client = stream_socket_accept($server);' . PHP_EOL
              . implode(PHP_EOL, $this->commands) . PHP_EOL
              . 'fclose($client);' . PHP_EOL
              . 'fclose($server);' . PHP_EOL
              . 'echo serialize($input);';

        $spec = array(
            0 => array('pipe', 'r'),
            1 => array('pipe', 'w')
        );
        $this->pipes = null;
        $this->process = proc_open('php', $spec, $this->pipes);
        fwrite($this->pipes[0], $code);
        fclose($this->pipes[0]);

        // Give the daemon time to spawn
        sleep(1);
    }

    /**
     * Returns all input received from the client.
     *
     * @return string Client input
     */
    public function getInput()
    {
        return $this->input;
    }

    /**
     * Closes the daemon process.
     *
     * @return void
     */
    public function close()
    {
        $code = stream_get_contents($this->pipes[1]);
        fclose($this->pipes[1]);
        proc_close($this->process);
        $this->input = unserialize($code);
    }
}
