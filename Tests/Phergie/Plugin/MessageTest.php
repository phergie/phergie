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
 * @package   Phergie_Tests
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2012 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Tests
 */

/**
 * Unit test suite for Phergie_Plugin_Message.
 *
 * @category Phergie
 * @package  Phergie_Tests
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Tests
 */
class Phergie_Plugin_MessageTest extends Phergie_Plugin_TestCase
{
    /**
     * Initialize a message event.
     *
     * @param string $message Message being sent.
     *
     * @return void
     */
    private function initializeMessageEvent($message, $inChannel = true)
    {
        $source = ((bool) $inChannel) ? '#channel' : 'private';

        $this->plugin->onLoad();
        $args = array(
            'receiver' => $source,
            'text' => $message
        );
        $event = $this->getMockEvent($args, $this->nick, $source);
        $this->plugin->setEvent($event);
    }

    /**
     * @dataProvider dataProviderMessages
     */
    public function testMessages($expected, $input, $inChannel, $prefix)
    {
        $this->setConfig('command.prefix',   $prefix);
        $this->setConfig('message.aliases', 'alias');
        $this->initializeMessageEvent($input, $inChannel);
        $this->assertEquals($expected, $this->plugin->getMessage());
    }

    public function dataProviderMessages()
    {
        $nick          = $this->nick;
        $withPrefix    = '!';
        $withoutPrefix = null;

        return array(
            array('hello',    $nick . ', hello', true,  $withoutPrefix),
            array('hello',    $nick . ', hello', false, $withoutPrefix),
            array('hi',       $nick . ': hi',    true,  $withPrefix),
            array('hi',       $nick . '> hi',    false, $withPrefix),
            array('hi',       $nick . ' hi',     true,  $withoutPrefix),
            array(false,      'random messages', true,  $withPrefix),
            array('foo bar',  'foo bar',         true,  $withoutPrefix),
            array('bar foo',  'bar foo',         false, $withPrefix),
            array('foo',      '!foo',            true,  $withPrefix),
            array('!foo',     '!foo',            true,  $withoutPrefix),
            array('hey',      'alias> hey',      true,  $withPrefix),
        );
    }

    public function testIsTargetedMessageWithoutAliases()
    {
        $this->initializeMessageEvent($this->connection->getNick() . ', hello');
        $this->assertTrue($this->plugin->isTargetedMessage());
    }

    public function testIsTargetedMessageWithAlias()
    {
        $this->setConfig('message.aliases', array('alias'));
        $this->initializeMessageEvent('alias, hello');
        $this->assertTrue($this->plugin->isTargetedMessage());
    }
}
