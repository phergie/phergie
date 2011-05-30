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
 * @package   Phergie_Plugin_Dice
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2011 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Plugin_Dice
 */

/**
 * Provide randomly generated numbers in response to die rolling requests,
 * such as "roll 3d6 + 2".
 *
 * @category Phergie
 * @package  Phergie_Plugin_Dice
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Plugin_Command
 * @uses     extension reflection
 * @uses     Phergie_Plugin_Message pear.phergie.org
 */
class Phergie_Plugin_Dice extends Phergie_Plugin_Abstract
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
    public function onCommandRoll($message)
    {
        if (preg_match('/(\d+)\s*d\s*(\d+)(\s*[-+]\s*\d+)?(.*)/i', $message, $matches)) {
            list (, $num, $die, $mod, $rest) = $matches;
            $roll = 0;
            for ($i = 0; $i < $num; $i++) {
                $roll += mt_rand(1, $die);
            }
            if (!empty($mod)) {
                $roll += intval(preg_replace('/\s+/', '', $mod));
            }

            $this->doPrivmsg($this->getEvent()->getSource(), 'roll for ' . $this->getEvent()->getNick() . ': ' . $num . 'd' . $die . $mod . $rest . ' --> ' . $roll);
        }
    }
}
