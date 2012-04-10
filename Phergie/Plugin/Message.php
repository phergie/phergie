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
     * Returns a regular expression that matches the bot's nick or aliases.
     *
     * @return string
     */
    private function getSelfRegex()
    {
        $me      = preg_quote($this->connection->getNick());
        $aliases = $this->getConfig('message.aliases');

        return '(?:' . implode('|', array_merge((array) $me, (array) $aliases)) . ')';
    }

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
        $event = $this->getEvent();

        $self = $this->getSelfRegex();

        $targetPattern = <<<REGEX
        {^
        \s*{$self}\s*[:>,\s].* # expect the bots name, followed by a [:>,\s]
        $}ix
REGEX;

        return !$event->isInChannel()
            || preg_match($targetPattern, $event->getText()) > 0;
    }

    /**
     * Allow for prefix and bot name aware extraction of a message
     *
     * @return string|bool $message The message, which is possibly targeted at the
     *                              bot or false if a prefix requirement failed
     */
    public function getMessage()
    {
        $event = $this->getEvent();

        $prefix = preg_quote($this->getConfig('command.prefix'));
        $self = $this->getSelfRegex();
        $message = $event->getText();

        // $prefixPattern matches : Phergie, do command <parameters>
        // where $prefix = 'do'   : do command <parameters>
        //                        : Phergie, command <parameters>
        $prefixPattern = <<<REGEX
        {^
        (?:
        	\s*{$self}\s*[:>,\s]\s* # start with bot name
			(?:{$prefix})?        # which is optionally followed by the prefix
        |
        	\s*{$prefix}          # or start with the prefix
        )
        \s*(.*)                   # always end with the message
        $}ix
REGEX;

        // $noPrefixPattern matches : Phergie, command <parameters>
        //                          : command <parameters>
        $noPrefixPattern = <<<REGEX
        {^
        \s*(?:{$self}\s*[:>,\s]\s*)? # optionally start with the bot name
        (.*?)                      # always end with the message
        $}ix
REGEX;

        $pattern = $noPrefixPattern;

        // If a prefix is set, force it as a requirement
        if ($prefix && $event->isInChannel()) {
            $pattern = $prefixPattern;
        }

        $match = null;

        if (!preg_match($pattern, $message, $match)) {
            return false;
        }

        return $match[1];
    }
}
