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
 */
class Phergie_Plugin_Help extends Phergie_Plugin_Abstract
{
    /**
     * Description of this plugin
     *
     * @var string
     */
    public $helpDesc = 'Provides access to plugin help information';

    /**
     * Information on the commands provided by this plugin
     *
     * @var array
     */
    public $helpCmds = array(
        array(
            'cmd' => 'help',
            'desc' => 'Show all actived plugins with help available'
        ),
        array(
            'cmd' => 'help [plugin]',
            'desc' => 'Shows commands line for a specific plugin'
        )
    );

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
     * Displays a list of plugins with help information available or 
     * commands available for a specific plugin.
     *
     * @param string $plugin Short name of the plugin for which commands 
     *        should be returned, else a list of plugins with help 
     *        information available is returned
     *
     * @return void
     */
    public function onCommandHelp($plugin = null)
    {
        $nick = $this->getEvent()->getNick();

        if (!$plugin) {
            $msg = 'These plugins below have help information available.';
            $this->doNotice($nick, $msg);
            $plugins = $this->getPluginHandler();
            foreach ($plugins as $plugin) {
                if (!empty($plugin->helpDesc)) {
                    $msg = $plugin->getName() . ': ' . $plugin->helpDesc;
                    $this->doNotice($nick, $msg);
                }
            }
        } else {
            if ($plugin = $this->getPluginHandler()->getPlugin($plugin)) {
                $msg
                    = 'The ' . 
                    $plugin->getName() . 
                    ' plugin exposes the commands shown below.';
                $this->doNotice($nick, $msg);
                if ($this->config['command.prefix']) {
                    $msg
                        = 'Note that these commands must be prefixed with "' .  
                        $this->config['command.prefix'] . 
                        '" (without quotes) when issued.';
                    $this->doNotice($nick, $msg);
                }
                foreach ($plugin->helpCmds as $cmd) {
                    $this->doNotice($nick, $cmd['cmd'] . ' - ' . $cmd['desc']);
                }
            } else {
                $this->doNotice($nick, 'That plugin is not loaded.');
            }
        }
    }
}
