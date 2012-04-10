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
 * @package   Phergie_Plugin_Daddy
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2011 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Plugin_Daddy
 */

/**
 * Simply responds to messages addressed to the bot that contain the phrase
 * "Who's your daddy?" and related variations.
 *
 * @category Phergie
 * @package  Phergie_Plugin_Daddy
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Plugin_Daddy
 */
class Phergie_Plugin_Daddy extends Phergie_Plugin_Abstract
{
    /**
     * Checks messages for the question to which it should respond and sends a
     * response when appropriate
     *
     * @return void
     */
    public function onPrivmsg()
    {
        $config = $this->getConfig();
        $prefix = $config['command.prefix'];
        $event = $this->getEvent();
        $text = $event->getArgument(1);
        $target = $event->getNick();
        $source = $event->getSource();
        $pattern
            = '/' . preg_quote($prefix) .
            '\s*?who\'?s y(?:our|a) ([^?]+)\??/iAD';
        if (preg_match($pattern, $text, $m)) {
            $msg = 'You\'re my ' . $m[1] . ', ' . $target . '!';
            $this->doPrivmsg($source, $msg);
        }
    }
}
