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
 * @package   Phergie_Plugin_Help
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2012 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Plugin_Help
 */

/**
 * Provides access to descriptions of plugins and the commands they provide.
 *
 * @category Phergie
 * @package  Phergie_Plugin_Help
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Plugin_Help
 * @uses     Phergie_Plugin_Command pear.phergie.org
 */
class Phergie_Plugin_Help extends Phergie_Plugin_Abstract
{
    /**
     * Registry of help data indexed by plugin name
     *
     * @var array
     */
    protected $registry;

    /**
     * Checks for dependencies.
     *
     * @return void
     */
    public function onLoad()
    {
        $this->getPluginHandler()->getPlugin('Command');
    }

    /**
     * Creates a registry of plugin metadata on connect.
     *
     * @return void
     */
    public function onConnect()
    {
        $this->populateRegistry();
    }

    /**
     * Creates a registry of plugin metadata.
     *
     * @return void
     */
    public function populateRegistry()
    {
        $this->registry = array();

        foreach ($this->plugins as $plugin) {
            $class = new ReflectionClass($plugin);
            $pluginName = strtolower($plugin->getName());

            // Parse the plugin description
            $docblock = $class->getDocComment();
            $annotations = $this->getAnnotations($docblock);
            if (isset($annotations['pluginDesc'])) {
                $pluginDesc = implode(' ', $annotations['pluginDesc']);
            } else {
                $pluginDesc = $this->parseShortDescription($docblock);
            }
            $this->registry[$pluginName] = array(
                'desc' => $pluginDesc,
                'cmds' => array()
            );

            // Parse command method descriptions
            $methodPrefix = Phergie_Plugin_Command::METHOD_PREFIX;
            $methodPrefixLength = strlen($methodPrefix);
            foreach ($class->getMethods() as $method) {
                if (strpos($method->getName(), $methodPrefix) !== 0) {
                    continue;
                }

                $cmd = strtolower(substr($method->getName(), $methodPrefixLength));
                $docblock = $method->getDocComment();
                $annotations = $this->getAnnotations($docblock);

                if (isset($annotations['pluginCmd'])) {
                    $cmdDesc = implode(' ', $annotations['pluginCmd']);
                } else {
                    $cmdDesc = $this->parseShortDescription($docblock);
                }

                $cmdParams = array();
                if (!empty($annotations['param'])) {
                    foreach ($annotations['param'] as $param) {
                        $match = null;
                        if (preg_match('/\h+\$([^\h]+)\h+/', $param, $match)) {
                            $cmdParams[] = $match[1];
                        }
                    }
                }

                $this->registry[$pluginName]['cmds'][$cmd] = array(
                    'desc' => $cmdDesc,
                    'params' => $cmdParams
                );
            }

            if (empty($this->registry[$pluginName]['cmds'])) {
                unset($this->registry[$pluginName]);
            }
        }
    }

    /**
     * Displays a list of plugins with help information available or
     * commands available for a specific plugin.
     *
     * @param string $query Optional short name of a plugin for which commands
     *        should be returned or a command; if unspecified, a list of
     *        plugins with help information available is returned
     *
     * @return void
     */
    public function onCommandHelp($query = null)
    {
        if ($query == 'refresh') {
            $this->populateRegistry();
        }

        $nick = $this->getEvent()->getNick();

        // Handle requests for a plugin list
        if (!$query) {
            $msg = 'These plugins have help information available: '
                 . implode(', ', array_keys($this->registry));
            $this->doPrivmsg($nick, $msg);
            return;
        }

        // Handle requests for plugin information
        $query = strtolower($query);
        if (isset($this->registry[$query])) {
            $msg = $query . ' - ' . $this->registry[$query]['desc'];
            $this->doPrivmsg($nick, $msg);

            $msg = 'Available commands - '
                 . implode(', ', array_keys($this->registry[$query]['cmds']));
            $this->doPrivmsg($nick, $msg);

            if ($this->getConfig('command.prefix')) {
                $msg
                    = 'Note that these commands must be prefixed with "'
                    . $this->getConfig('command.prefix')
                    . '" (without quotes) when issued in a public channel.';
                $this->doPrivmsg($nick, $msg);
            }
        }

        // Handle requests for command information
        foreach ($this->registry as $plugin => $data) {
            if (empty($data['cmds'])) {
                continue;
            }

            $result = preg_grep('/^' . $query . '$/i', array_keys($data['cmds']));
            if (!$result) {
                continue;
            }

            $cmd = $data['cmds'][array_shift($result)];
            $msg = $query;
            if (!empty($cmd['params'])) {
                $msg .= ' [' . implode('] [', $cmd['params']) . ']';
            }
            $msg .= ' - ' . $cmd['desc'];
            $this->doPrivmsg($nick, $msg);
        }
    }

    /**
     * Parses and returns the short description from a docblock.
     *
     * @param string $docblock Docblock comment code
     *
     * @return string Short description (i.e. content from the start of the
     *         docblock up to the first double-newline)
     */
    protected function parseShortDescription($docblock)
    {
        $desc = preg_replace(
            array('#^\h*\*\h*#m', '#^/\*\*\h*\v+\h*#'
                , '#(?:\r?\n){2,}.*#s', '#\s*\v+\s*#'),
            array('', '', '', ' '),
            $docblock
        );
        return $desc;
    }

    /**
     * Taken from PHPUnit/Util/Test.php and modified to fix an issue with
     * tag content spanning multiple lines.
     *
     * PHPUnit
     *
     * Copyright (c) 2002-2012, Sebastian Bergmann <sb@sebastian-bergmann.de>.
     * All rights reserved.
     *
     * Redistribution and use in source and binary forms, with or without
     * modification, are permitted provided that the following conditions
     * are met:
     *
     *   * Redistributions of source code must retain the above copyright
     *     notice, this list of conditions and the following disclaimer.
     *
     *   * Redistributions in binary form must reproduce the above copyright
     *     notice, this list of conditions and the following disclaimer in
     *     the documentation and/or other materials provided with the
     *     distribution.
     *
     *   * Neither the name of Sebastian Bergmann nor the names of his
     *     contributors may be used to endorse or promote products derived
     *     from this software without specific prior written permission.
     *
     * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
     * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
     * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
     * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
     * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
     * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
     * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
     * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
     * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
     * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
     * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
     * POSSIBILITY OF SUCH DAMAGE.
     *
     * @param string $docblock docblock to parse
     *
     * @return array
     */
    protected function getAnnotations($docblock)
    {
        $annotations = array();

        $regex = '/@(?P<name>[A-Za-z_-]+)(?:[ \t]+(?P<value>.*?))?(?:\*\/|\* )/ms';

        if (preg_match_all($regex, $docblock, $matches)) {
            $numMatches = count($matches[0]);

            for ($i = 0; $i < $numMatches; ++$i) {
                $annotation = $matches['value'][$i];
                $annotation = preg_replace('/\s*\v+\s*\*\s*/', ' ', $annotation);
                $annotation = rtrim($annotation);
                $annotations[$matches['name'][$i]][] = $annotation;
            }
        }

        return $annotations;
    }
}
