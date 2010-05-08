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
 * @package   Phergie
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2010 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie
 */

require_once(dirname(__FILE__) . '/TestCase.php');

/**
 * Unit test suite for Pherge_Plugin_TerryChay.
 *
 * @category Phergie
 * @package  Phergie_Tests
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie
 */
class Phergie_Plugin_TerryChayTest extends Phergie_Plugin_TestCase
{
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->setPlugin(new Phergie_Plugin_TerryChay());
        $config = new Phergie_Config();
        $handler = new Phergie_Plugin_Handler($config, $this->handler);
        $this->plugin->setPluginHandler($handler);
        $handler->addPlugin($this->plugin);
        $handler->addPlugin(new Phergie_Plugin_Http($config));
        $this->plugin->setConfig($config);
        $this->connection->setNick('phergie');
        $this->plugin->onLoad();
    }

    /**
     * @event Phergie_Event_Request::privmsg
     * @eventArg #zftalk
     * @eventArg tychay
     */
    public function testWithTyChay()
    {
        $this->plugin->onPrivMsg();
        $this->assertHasEvent(Phergie_Event_Command::TYPE_PRIVMSG);
    }

    /**
     * @event Phergie_Event_Request::privmsg
     * @eventArg #zftalk
     * @eventArg terrychay
     */
    public function testWithTerryChay()
    {
        $this->plugin->onPrivMsg();
        $this->assertDoesNotHaveEvent(Phergie_Event_Command::TYPE_PRIVMSG,
                              'string "terrychay" should not invoke a response');
    }
    
    /**
     * @event Phergie_Event_Request::privmsg
     * @eventArg #zftalk
     * @eventArg terry chay
     */
    public function testWithTerry_Chay()
    {
        $this->plugin->onPrivMsg();
        $this->assertHasEvent(Phergie_Event_Command::TYPE_PRIVMSG,
                              'string "terry chay" should invoke a response');
    }

    /**
     * @event Phergie_Event_Request::privmsg
     * @eventArg #zftalk
     * @eventArg Elazar is not Mr. Chay
     */
    public function testWithNoTyChay()
    {
        $this->plugin->onPrivMsg();
        $this->assertDoesNotHaveEvent(Phergie_Event_Command::TYPE_PRIVMSG,
                                      'Failed asserting that elazar is not ' .
                                      'tychay');
    }
}