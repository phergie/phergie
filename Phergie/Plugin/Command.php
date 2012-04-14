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
 * @package   Phergie_Plugin_Command
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2012 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Plugin_Command
 */

/**
 * Handles parsing and execution of commands sent by users via messages sent
 * to channels in which the bot is present or directly to the bot.
 *
 * @category Phergie
 * @package  Phergie_Plugin_Command
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Plugin_Command
 * @uses     extension reflection
 * @uses     Phergie_Plugin_Message pear.phergie.org
 */
class Phergie_Plugin_Command extends Phergie_Plugin_Abstract
{
    /**
     * Prefix for command method names
     *
     * @var string
     */
    const METHOD_PREFIX = 'onCommand';

    /**
     * Cache for command lookups used to confirm that methods exist and
     * parameter counts match
     *
     * @var array
     */
    protected $methods = array();

    /**
     * Load the Message plugin
     *
     * @return void
     */
    public function onLoad()
    {
        $plugins = $this->getPluginHandler();
        $plugins->getPlugin('Message');
    }

    /**
     * Returns a list of detected methods for processing commands.
     *
     * @return array List of method names
     */
    public function getMethods()
    {
        if (empty($this->methods)) {
            $this->populateMethodCache();
        }
        return array_keys($this->methods);
    }

    /**
     * Populates the methods cache.
     *
     * @return void
     */
    public function populateMethodCache()
    {
        foreach ($this->getPluginHandler()->getPlugins() as $plugin) {
            $reflector = new ReflectionClass($plugin);
            foreach ($reflector->getMethods() as $method) {
                $name = $method->getName();
                if (strpos($name, self::METHOD_PREFIX) === 0
                    && !isset($this->methods[$name])
                ) {
                    $this->methods[$name] = array(
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
        if (empty($this->methods)) {
            $this->populateMethodCache();
        }

        // Check for a prefixed message
        $msg = $this->plugins->message->getMessage();
        if ($msg === false) {
            return;
        }

        // Separate the command and arguments
        $parsed = preg_split('/\s+/', $msg, 2);
        $command = strtolower(array_shift($parsed));
        $args = count($parsed) ? array_shift($parsed) : '';

        // Resolve aliases to their corresponding commands
        $aliases = $this->getConfig('command.aliases', array());
        $result = preg_grep(
            '/^' . preg_quote($command, '/')
            . '$/i', array_keys($aliases)
        );

        if ($result) {
            $command = $aliases[array_shift($result)];
        }

        // Check to ensure the command exists
        $method = self::METHOD_PREFIX . ucfirst($command);
        if (empty($this->methods[$method])) {
            return;
        }

        // If no arguments are passed...
        if (empty($args)) {

            // If the method requires no arguments, call it
            if (empty($this->methods[$method]['required'])) {
                $this->getPluginHandler()->$method();
            }

        } else {
            // If arguments are passed...

            // Parse the arguments
            if ('"' == substr($args, 0, 1)) {
                preg_match_all('/("[^"]*")|(\S+)/', $args, $args);
                $argsIn = $args[0];
                $i = 1;
                $args = array();
                $methodArgsTotal = $this->methods[$method]['total'];
                foreach ($argsIn as $arg) {
                    if ($i < $methodArgsTotal) {
                        $args[] = $arg;
                        $i++;
                    } else {
                        if (empty($args[$methodArgsTotal])) {
                            $args[$methodArgsTotal] = $arg;
                        } else {
                            $args[$methodArgsTotal] .= ' '.$arg;
                        }
                    }
                }
                $args = array_values($args);
            } else {
                $args = preg_split('/\s+/', $args, $this->methods[$method]['total']);
            }

            // If the minimum arguments are passed, call the method
            if ($this->methods[$method]['required'] <= count($args)) {
                call_user_func_array(
                    array($this->getPluginHandler(), $method),
                    $args
                );
            }
        }

    }
}
