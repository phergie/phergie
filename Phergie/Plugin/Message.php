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
 * @package   Phergie_Plugin_Message
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2011 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Plugin_Message
 */

/**
 * Generalized plugin providing utility methods for
 * prefix and bot named based message extraction.
 *
 * @category Phergie
 * @package  Phergie_Plugin_Message
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Plugin_Message
 */
class Phergie_Plugin_Message extends Phergie_Plugin_Abstract
{
    /**
     * Check whether a message is specifically targeted at the bot.
     * This is the case when the message starts with the bot's name
     * followed by [,:>] or when it is a private message.
     *
     * @return boolean true when the message is specifically targeted at the bot,
     *                 false otherwise.
     */
    public function isTargetedMessage()
    {
        return $this->getMessage() !== false;
    }

    /**
     * Allow for prefix and bot name aware extraction of a message
     *
     * @return string|bool $message The message, which is possibly targeted at the
     *                              bot or false if a prefix requirement failed
     */
    public function getMessage()
    {
        $event   = $this->getEvent();
        $message = $event->getText();
        $prefix  = $this->getConfig('command.prefix');
        $symbols = array(':', ' ', ',', '>');
        $nicks   = array_merge(
            (array) $this->connection->getNick(),
            (array) $this->getConfig('message.aliases')
        );

        // Format '<nick>[:|,|>|<space>] <message>'
        foreach ($nicks as $nick) {
            $length = strlen($nick);
            if (substr($message, 0, $length) == $nick
                && in_array(substr($message, $length, 1), $symbols)
            ) {
                return trim(substr($message, $length + 1));
            }
        }

        // Format '<prefix><message>' (note that $prefix could be null)
        $length = strlen($prefix);
        if (substr($message, 0, $length) === $prefix) {
            return trim(substr($message, $length));
        }

        // Private message or without configured prefix
        if (!$event->isInChannel() || $prefix === null) {
            return trim($message);
        }

        return false;
    }
}
