<?php

/**
 * Handles parsing and execution of commands sent by users via messages sent 
 * to channels in which the bot is present or directly to the bot.
 */
class Phergie_Plugin_Command extends Phergie_Plugin_Abstract
{
    /**
     * Cache for command lookups used to confirm that methods exist and 
     * parameter counts match
     *
     * @var array
     */
    protected $_methods = array();

    /**
     * Prefix for command method names
     *
     * @var string
     */
    protected $_prefix = 'onCommand';

    /**
     * Populates the methods cache.
     *
     * @return void
     */
    protected function _populateMethodCache()
    {
        foreach ($this->getPluginHandler() as $plugin) {
            $reflector = new ReflectionClass($plugin);
            foreach ($reflector->getMethods() as $method) {
                $name = $method->getName();
                if (strpos($name, $this->_prefix) === 0 
                    && !isset($this->_methods[$name])) {
                    $this->_methods[$name] = array(
                        'total' => $method->getNumberOfParameters(),
                        'required' => $method->getNumberOfRequiredParameters()
                    );
                }
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
        // Populate the methods cache if needed
        if (empty($this->_methods)) {
            $this->_populateMethodCache();
        }

        // Get the content of the message
        $event = $this->getEvent();
        $msg = trim($event->getText());

        // Check for the command prefix if one is set and needed
        if (!empty($this->_config['command.prefix']) && $event->isInChannel()) {
            if (strpos($msg, $this->_config['command.prefix']) !== 0) {
                return;
            } else {
                $msg = substr($msg, strlen($this->_config['command.prefix']));
            }
        }

        // Separate the command and arguments
        $parsed = preg_split('/\s+/', $msg, 2);
        $method = $this->_prefix . ucfirst(strtolower(array_shift($parsed))); 
        $args = count($parsed) ? array_shift($parsed) : '';

        // Check to ensure the command exists
        if (empty($this->_methods[$method])) {
            return;
        }

        // If no arguments are passed...
        if (empty($args)) {

            // If the method requires no arguments, call it
            if (empty($this->_methods[$method]['required'])) {
                $this->getPluginHandler()->$method();
            }

        // If arguments are passed...
        } else {

            // Parse the arguments
            $args = preg_split('/\s+/', $args, $this->_methods[$method]['total']);

            // If the minimum arguments are passed, call the method 
            if ($this->_methods[$method]['required'] <= count($args)) {
                call_user_func_array(array($this->getPluginHandler(), $method), $args);
            }
        }
    }
}
