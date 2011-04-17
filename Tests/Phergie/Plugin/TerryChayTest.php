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
 * @copyright 2008-2010 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Tests
 */

/**
 * Unit test suite for Phergie_Plugin_TerryChay.
 *
 * @category Phergie
 * @package  Phergie_Tests
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Tests
 */
class Phergie_Plugin_TerryChayTest extends Phergie_Plugin_TestCase
{
    /**
     * Chayism used as a consistent response when related events are
     * triggered
     *
     * @var string
     */
    private $chayism = 'Terry Chay doesn\'t need a framework; he already knows everyone\'s code';

    /**
     * Configures the mock plugin handler to return a mock Http plugin with
     * a mock response object populated with predetermined content.
     *
     * @return void
     */
    public function setUpHttpClient()
    {
        $response = $this->getMock(
            'Phergie_Plugin_Http_Response', array('getContent')
        );
        $response
            ->expects($this->any())
            ->method('getContent')
            ->will($this->returnValue($this->chayism));

        $plugin = $this->requirePlugin('Http');
        $plugin
            ->expects($this->any())
            ->method('get')
            ->will($this->returnValue($response));
    }

    /**
     * Tests that the plugin requires the Http plugin as a dependency.
     *
     * @return void
     */
    public function testRequiresHttpPlugin()
    {
        $this->assertRequiresPlugin('Http');
        $this->plugin->onLoad();
    }

    /**
     * Data provider for testPrivmsgTriggerReturnsChayism().
     *
     * @return array Enumerated array of enumerated arrays each containing
     *               a set of parameters for a single call to
     *               testPrivmsgTriggerReturnsChayism()
     */
    public function dataProviderTestPrivmsgTriggerReturnsChayism()
    {
        return array(
            array('terry chay'),
            array('terry  chay'),
            array('tychay'),
            array('!tychay'),
            array('! tychay'),
            array('foo tychay bar'),
        );
    }

    /**
     * Tests that appropriate triggers result in a response with a Chayism.
     *
     * @param String $trigger The trigger to test
     *
     * @return void
     * @dataProvider dataProviderTestPrivmsgTriggerReturnsChayism
     */
    public function testPrivmsgTriggerReturnsChayism($trigger)
    {
        $this->setConfig('command.prefix', '!');
        $this->setUpHttpClient();
        $args = array(
            'receiver' => $this->source,
            'text' => $trigger
        );
        $event = $this->getMockEvent('privmsg', $args);
        $this->plugin->setEvent($event);
        $this->assertEmitsEvent(
            'privmsg', array($this->source, 'Fact: ' . $this->chayism)
        );
        $this->plugin->onPrivmsg();
    }

    /**
     * Tests that lack of an appropriate trigger results in no response with
     * a Chayism.
     *
     * @return void
     */
    public function testNoPrivmsgTriggerDoesNotReturnChayism()
    {
        $args = array(
            'receiver' => $this->source,
            'text' => 'foo bar baz'
        );
        $event = $this->getMockEvent('privmsg', $args);
        $this->plugin->setEvent($event);
        $this->assertDoesNotEmitEvent(
            'privmsg', array($this->source, 'Fact: ' . $this->chayism)
        );
        $this->plugin->onPrivmsg();
    }
}
