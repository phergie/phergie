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
 * @copyright 2008-2010 Phergie Development Team (http://phergie.org)
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
 *
 * @pluginDesc Provides access to plugin help information
 */
class Phergie_Plugin_Help extends Phergie_Plugin_Abstract
{

    /**
     * Holds the registry of help data indexed by plugin name
     *
     * @var array
     */
    protected $registry;

    /**
     * Whether the registry has been alpha sorted
     *
     * @var bool
     */
    protected $registry_sorted = false;

    /**
     * Checks for dependencies.
     *
     * @return void
     */
    public function onLoad()
    {
        $this->getPluginHandler()->getPlugin('Command');
        $this->register($this);
    }

    /**
     * Displays a list of plugins with help information available or
     * commands available for a specific plugin.
     *
     * @param string $plugin Short name of the plugin for which commands
     *        should be returned, else a list of plugins with help
     *        information available is returned
     *
     * @return void
     *
     * @pluginCmd Show all active plugins with help available
     * @pluginCmd [plugin] Shows commands line for a specific plugin
     */
    public function onCommandHelp($plugin = null)
    {
        $nick = $this->getEvent()->getNick();

        if (!$plugin) {
            // protect from sorting the registry each time help is called
            if (!$this->registry_sorted) {
                asort($this->registry);
                $this->registry_sorted = true;
            }

            $msg = 'These plugins below have help information available.';
            $this->doPrivMsg($nick, $msg);

            foreach ($this->registry as $plugin => $data) {
                $this->doPrivMsg($nick, "{$plugin} - {$data['desc']}");
            }
        } else {
            if (isset($this->getPluginHandler()->{$plugin})
                && isset($this->registry[strtolower($plugin)]['cmd'])
            ) {
                $msg
                    = 'The ' .
                    $plugin .
                    ' plugin exposes the commands shown below.';
                $this->doPrivMsg($nick, $msg);
                if ($this->getConfig('command.prefix')) {
                    $msg
                        = 'Note that these commands must be prefixed with "' .
                        $this->getConfig('command.prefix') .
                        '" (without quotes) when issued in a public channel.';
                    $this->doPrivMsg($nick, $msg);
                }

                foreach ($this->registry[strtolower($plugin)]['cmd']
                    as $cmd => $descs
                ) {
                    foreach ($descs as $desc) {
                        $this->doPrivMsg($nick, "{$cmd} {$desc}");
                    }
                }

            } else {
                $this->doPrivMsg($nick, 'That plugin is not loaded.');
            }
        }
    }

    /**
     * Sets the description for the plugin instance
     *
     * @param Phergie_Plugin_Abstract $plugin      plugin instance
     * @param string                  $description plugin description
     *
     * @return void
     */
    public function setPluginDescription(
        Phergie_Plugin_Abstract $plugin,
        $description
    ) {
        $this->registry[strtolower($plugin->getName())]
                ['desc'] = $description;
    }

    /**
     * Sets the description for the command on the plugin instance
     *
     * @param Phergie_Plugin_Abstract $plugin      plugin instance
     * @param string                  $command     from onCommand method
     * @param string                  $description command description
     *
     * @return void
     */
    public function setCommandDescription(
        Phergie_Plugin_Abstract $plugin,
        $command,
        array $description
    ) {
        $this->registry[strtolower($plugin->getName())]
            ['cmd'][$command] = $description;
    }

    /**
     * registers the plugin with the help plugin. this will parse the docblocks
     * for specific annotations that this plugin will respond with when
     * queried.
     *
     * @param Phergie_Plugin_Abstract $plugin plugin instance
     *
     * @return void
     */
    public function register(Phergie_Plugin_Abstract $plugin)
    {
        $class = new ReflectionClass($plugin);

        $annotations = self::parseAnnotations($class->getDocComment());
        if (isset($annotations['pluginDesc'])) {
            $this->setPluginDescription(
                $plugin,
                join(' ', $annotations['pluginDesc'])
            );
        }

        foreach ($class->getMethods() as $method) {
            if (strpos($method->getName(), 'onCommand') !== false) {
                $annotations = self::parseAnnotations($method->getDocComment());
                if (isset($annotations['pluginCmd'])) {
                    $cmd = strtolower(substr($method->getName(), 9));
                    $this->setCommandDescription(
                        $plugin,
                        $cmd,
                        $annotations['pluginCmd']
                    );
                }
            }
        }
    }

    /**
     * Taken from PHPUnit/Util/Test.php:243 and modified to fix an issue
     * with tag content spanning multiple lines.
     *
     * PHPUnit
     *
     * Copyright (c) 2002-2010, Sebastian Bergmann <sb@sebastian-bergmann.de>.
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
    protected static function parseAnnotations($docblock)
    {
        $annotations = array();

        $regex = '/@(?P<name>[A-Za-z_-]+)(?:[ \t]+(?P<value>.*?))?(?:\*\/|\* @)/ms';

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
