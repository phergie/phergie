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
 * @copyright 2008-2011 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Tests
 */

/**
 * Unit test suite for Phergie_Plugin_Dice.
 *
 * @category Phergie
 * @package  Phergie_Tests
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Tests
 */
class Phergie_Plugin_DiceTest extends Phergie_Plugin_TestCase
{
    /**
     * Tests for appropriate plugin requirements.
     *
     * @return void
     */
    public function testPluginRequirements()
    {
        $this->assertRequiresPlugin('Command');
        $this->plugin->onLoad();
    }
    
    /**
     * Initialize a die roll event.
     *
     * @param string $roll Die roll being checked
     *
     * @return void
     */
    private function initializeRollEvent($roll)
    {  
        $this->plugin->onLoad();
        $args = array(
            'receiver' => $this->source,
            'text' => 'roll ' . $roll
        );
        $event = $this->getMockEvent('privmsg', $args);
        $this->plugin->setEvent($event);
    }

    /**
     * Checks for a specified response to a die roll event.
     *
     * @param string $roll Die roll being checked
     * @param string $expected Expected response
     *
     * @return void
     */
    private function checkForRollResponse($roll, $expected)
    {
        if (substr($expected, 0, 1) != '/') {
            $this->assertEmitsEvent('privmsg', array($this->source, $expected));
        } else {
            $callback = create_function(
                '$plugin, $type, $args',
                'if (get_class($plugin) != "' . $this->pluginClass . '"
                || $type != "privmsg"
                || !preg_match(\'' . $expected . '\', $args[1])) {
                    trigger_error("Instance of ' . $this->pluginClass
                . ' expected \"' . $expected . '\" but was actually \""'
                . ' . $args[1] . "\"", E_USER_ERROR);
                }'
            );
            $this->events
                ->expects($this->at(0))
                ->method('addEvent')
                ->will($this->returnCallback($callback));
        }
        $this->plugin->onCommandRoll($roll);
    }
    
    /**
     * Assertion that checks the response to a specific dice roll.
     *
     * @param string $roll Die roll being checked
     * @param string $expected Expected response
     *
     * @return void
     */
    private function assertRoll($roll, $expected)
    {  
        $this->initializeRollEvent($roll);
        $this->checkForRollResponse($roll, $expected);
    }

    /**
     * Tests for the plugin command triggered with no die roll.
     *
     * @return void
     */
    public function testEmptyRoll()
    {
        $this->initializeRollEvent('');
        $this->assertDoesNotEmitEvent('privmsg');
        $this->plugin->onCommandSpell('');
    }

    /**
     * Tests for an invalid die roll.
     *
     * @return void
     */
    public function testInvalidRoll()
    {
        $this->initializeRollEvent('x');
        $this->assertDoesNotEmitEvent('privmsg');
        $this->plugin->onCommandSpell('x');
    }

    /**
     * Tests for simple die rolls.
     *
     * @return void
     */
    public function testSimpleRolls()
    {
        $this->assertRoll('0d0', 'roll for nick: 0d0 --> 0');
        $this->assertRoll('0d1', 'roll for nick: 0d1 --> 0');
        $this->assertRoll('1d0', 'roll for nick: 1d0 --> 0');
        $this->assertRoll('1d1', 'roll for nick: 1d1 --> 1');
        $this->assertRoll('1d2', '/roll for nick: 1d2 --> (1|2)/');
    }

    /**
     * Tests for die roll with modifiers.
     *
     * @return void
     */
    public function testModifiedRolls()
    {
        $this->assertRoll('1d1 + 1', 'roll for nick: 1d1 + 1 --> 2');
        $this->assertRoll('1d1 - 1', 'roll for nick: 1d1 - 1 --> 0');
    }

    /**
     * Tests for die roll with descriptions.
     *
     * @return void
     */
    public function testDescribedRolls()
    {
        $this->assertRoll('1d1 for testing', 'roll for nick: 1d1 for testing --> 1');
    }
}
