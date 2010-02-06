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
 * @package   Phergie_Plugin_Puppet
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2010 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Plugin_Puppet
 */

/**
 * Allows a user to effectively speak and act as the bot.
 *
 * @category Phergie 
 * @package  Phergie_Plugin_Puppet
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Plugin_Puppet
 * @uses     Phergie_Plugin_Command pear.phergie.org
 */
class Phergie_Plugin_Puppet extends Phergie_Plugin_Abstract
{
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
     * Handles a request for the bot to repeat a given message in a specified
     * channel.
     *
     * <code>say #chan message</code>
     *
     * @param string $channel Name of the channel
     * @param string $message Message to repeat
     *
     * @return void
     */
    public function onCommandSay($channel, $message)
    {
        $this->doPrivmsg($channel, $message);
    }

    /**
     * Handles a request for the bot to repeat a given action in a specified
     * channel.
     *
     * <code>act #chan action</code>
     *
     * @param string $channel Name of the channel
     * @param string $action  Action to perform
     *
     * @return void
     */
    public function onCommandAct($channel, $action)
    {
        $this->doAction($channel, $action);
    }

    /**
     * Handles a request for the bot to send the server a raw message
     *
     * <code>raw message</code>
     *
     * @param string $message Message to send
     *
     * @return void
     */
    public function onCommandRaw($message)
    {
        $this->doRaw($message);
    }
}
