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

require_once dirname(__FILE__) . '/TestCase.php';

/**
 * Unit test suite for Pherge_Plugin_SpellCheck.
 *
 * @category Phergie
 * @package  Phergie_Tests
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie
 */
class Phergie_Plugin_SpellCheckTest extends Phergie_Plugin_TestCase
{

    /**
     * Current SpellCheck plugin instance
     *
     * @var Phergie_Plugin_SpellCheck
     */
    protected $spell;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     * 
     * @return void
     */
    protected function setUp()
    {
        $this->config = array('spellcheck.lang' => 'en');

        $this->spell = new Phergie_Plugin_SpellCheck();
        $this->setPlugin(new Phergie_Plugin_Command());
        
        $config = $this->plugin->getConfig();
        
        $handler = new Phergie_Plugin_Handler($config, $this->handler);
        $this->plugin->setPluginHandler($handler);
        
        $handler->addPlugin($this->plugin);
        $handler->addPlugin($this->spell);

        $this->spell->setEventHandler($this->handler);
        $this->spell->setConnection($this->connection);
    }

    /**
     * @event Phergie_Event_Request::privmsg
     * @eventArg #zftalk
     * @eventArg spell
     */
    public function testSpell()
    {
        $this->spell->onLoad();
        
        $this->copyEvent();
        $this->plugin->onPrivMsg();
        $this->assertDoesNotHaveEvent(Phergie_Event_Command::TYPE_PRIVMSG);
    }

    /**
     * @event Phergie_Event_Request::privmsg
     * @eventArg #phergie
     * @eventArg spell test
     */
    public function testSpellTest()
    {
        $this->spell->onLoad();
        
        $this->copyEvent();
        $this->plugin->onPrivMsg();

        $events = $this->getResponseEvents(Phergie_Event_Command::TYPE_PRIVMSG);
        
        $this->assertEquals(1, count($events));
        foreach ($events as $event) {
            $args = $event->getArguments();
            
            $this->assertEquals('#phergie', $args[0]);
            
            $this->assertContains('CheckSpellUser:', $args[1]);
            $this->assertContains('test', $args[1]);
            $this->assertContains('correct', $args[1]);
        }            
    }

    /**
     * @event Phergie_Event_Request::privmsg
     * @eventArg #phergie
     * @eventArg spell testz
     */
    public function testSpellTestz()
    {
        $this->spell->onLoad();
        
        $this->copyEvent();
        $this->plugin->onPrivMsg();
        
        $events = $this->getResponseEvents(Phergie_Event_Command::TYPE_PRIVMSG);
        
        $this->assertEquals(1, count($events));
        foreach ($events as $event) {
            $args = $event->getArguments();
            
            $this->assertEquals('#phergie', $args[0]);
            
            $this->assertContains('CheckSpellUser:', $args[1]);
            $this->assertRegExp('/([a-z]+, ){4}/', $args[1]);
            $this->assertContains('testz', $args[1]);
            $this->assertContains('test,', $args[1]);
        }
    }

    /**
     * @event Phergie_Event_Request::privmsg
     * @eventArg #phergie
     * @eventArg spell testz
     */
    public function testSpellMoreSuggestions()
    {
        $config = $this->spell->getConfig();
        
        $this->copyEvent();
        $config['spellcheck.limit'] = 6;
        
        $this->spell->onLoad();
        $this->plugin->onPrivMsg();
        
        $events = $this->getResponseEvents(Phergie_Event_Command::TYPE_PRIVMSG);
        
        $this->assertEquals(1, count($events));
        foreach ($events as $event) {
            $args = $event->getArguments();
            
            $this->assertEquals('#phergie', $args[0]);
            
            $this->assertContains('CheckSpellUser:', $args[1]);
            $this->assertRegExp('/([a-z]+, ){5}/', $args[1]);
            $this->assertContains('testz', $args[1]);
            $this->assertContains('test,', $args[1]);
        }
    }

    /**
     * @event Phergie_Event_Request::privmsg
     * @eventArg #phergie
     * @eventArg spell qwertyuiopasdfghjklzxcvbnm
     */
    public function testSpellNoSuggestions()
    {
        $this->spell->onLoad();
        
        $this->copyEvent();
        $this->plugin->onPrivMsg();
        
        $events = $this->getResponseEvents(Phergie_Event_Command::TYPE_PRIVMSG);
        
        $this->assertEquals(1, count($events));
        foreach ($events as $event) {
            $args = $event->getArguments();
            
            $this->assertEquals('#phergie', $args[0]);
            
            $this->assertContains('CheckSpellUser:', $args[1]);
            $this->assertContains('find any suggestions', $args[1]);
        }
    }
    
    /**
     * Copy event from command to spell plugin
     * 
     * @return void
     */
    protected function copyEvent()
    {
        $hostmask = Phergie_Hostmask::fromString('CheckSpellUser!test@testing.org');

        $event = $this->plugin->getEvent();
        $event->setHostmask($hostmask);

        $this->spell->setEvent($event);
    }

}
