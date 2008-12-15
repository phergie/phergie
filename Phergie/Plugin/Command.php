<?php

require_once 'Phergie/Plugin/Abstract.php';

/**
 * Handles parsing and execution of commands sent by users via messages sent 
 * to channels in which the bot is present or directly to the bot.
 */
abstract class Phergie_Plugin_Command extends Phergie_Plugin_Abstract
{
    /**
     * Cache for command lookups used to confirm that methods exist and 
     * parameter counts match
     *
     * @var array
     */
    private $_methods = array();

    /**
     * Initialize the methods cache when the bot connects to the server.
     *
     * @return void
     */
    public function onConnect()
    {
        $reflector = new ReflectionClass(get_class($this));
        foreach ($reflector->getMethods() as $method) {
            $name = $method->getName();
            if (strpos($name, 'onDo') === 0) {
                $this->_methods[strtolower(substr($name, 4))] = array(
                    'total' => $method->getNumberOfParameters(),
                    'required' => $method->getNumberOfRequiredParameters()
                );
            }
        }
    }

    /**
     * Parses a given message and, if its format corresponds to that of a
     * defined command, calls the handler method for that command with any
     * provided parameters.
     *
     * @return void
     */
    public function onPrivmsg()
    {
        // Get the content of the message
        $msg = trim($this->_event->getText());

        // Check for the command prefix if one is set
        if (!empty($this->_config['command.prefix'])) {
            if (strpos($msg, $this->_config['command.prefix']) !== 0) {
                return;
            } else {
                $msg = substr($msg, strlen($this->_config['command.prefix']));
            }
        }

        // Separate the command and arguments
        $parsed = preg_split('/\s+/', $msg, 2);
        $cmd = strtolower(array_shift($parsed));
        $args = count($parsed) ? array_shift($parsed) : '';
        $method = 'onDo' . ucfirst($cmd); 

        // Check to ensure the command exists
        if (empty($this->_methods[$cmd])) {
            return;
        }

        // If no arguments are passed...
        if (empty($args)) {

            // If the method requires no arguments, call it
            if (empty($this->_methods[$cmd]['required'])) {
                $this->$method();
            }

        // If arguments are passed...
        } else {

            // Parse the arguments
            $args = preg_split('/\s+/', $args, $this->_methods[$cmd]['total']);

            // If the minimum arguments are passed, call the method 
            if ($this->_methods[$cmd]['required'] <= count($args)) {
                call_user_func_array(array($this, $method), $args);
            }
        }
    }
}
