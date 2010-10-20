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
 * Unit test suite for Phergie_Plugin_SpellCheck.
 *
 * @category Phergie
 * @package  Phergie_Tests
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Tests
 */
class Phergie_Plugin_SpellCheckTest extends Phergie_Plugin_TestCase
{
    /**
     * Checks for the pspell extension.
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        if (!extension_loaded('pspell')) {
            $this->markTestSkipped('pspell extension not available');
        }
    }

    /**
     * Tests for the plugin failing to load when the language setting is not
     * specified.
     *
     * @return void
     */
    public function testLanguageSettingNotSet()
    {
        try {
            $this->plugin->onLoad();
            $this->fail('Expected exception was not thrown');
        } catch (Phergie_Plugin_Exception $e) {
            return;
        }
        $this->fail('Unexpected exception was thrown');
    }

    /**
     * Tests for the plugin requiring the Command plugin as a dependency.
     *
     * @return void
     */
    public function testRequiresCommandPlugin()
    {
        $this->setConfig('spellcheck.lang', 'en');
        $this->assertRequiresPlugin('Command');
        $this->plugin->onLoad();
    }

    /**
     * Tests for the plugin failing to load because of a dictionary error.
     *
     * @return void
     */
    public function testLoadDictionaryError()
    {
        $this->setConfig('spellcheck.lang', 'foo');
        try {
            $this->plugin->onLoad();
            $this->fail('Expected exception not thrown');
        } catch (Phergie_Plugin_Exception $e) {
            return;
        }
        $this->fail('Unexpected exception was thrown');
    }

    /**
     * Initializes a spell check event.
     *
     * @param string $word Word to be checked
     *
     * @return void
     */
    private function initializeSpellCheckEvent($word)
    {
        $this->setConfig('spellcheck.lang', 'en');
        $this->plugin->onLoad();
        $args = array(
            'receiver' => $this->source,
            'text' => 'spell ' . $word
        );
        $event = $this->getMockEvent('privmsg', $args);
        $this->plugin->setEvent($event);
    }

    /**
     * Checks for a specified response to a spell check event.
     *
     * @param string $word     Work being checked
     * @param string $response Expected response
     *
     * @return void
     */
    private function checkForSpellCheckResponse($word, $response)
    {
        $this->assertEmitsEvent('privmsg', array($this->source, $response));
        $this->plugin->onCommandSpell($word);
    }

    /**
     * Tests for the plugin returning a response for a correctly spelled word.
     *
     * @return void
     */
    public function testRespondsForCorrectlySpelledWord()
    {
        $word = 'test';
        $this->initializeSpellCheckEvent($word);
        $response = $this->nick . ': The word "' . $word
            . '" seems to be spelled correctly.';
        $this->checkForSpellCheckResponse($word, $response);
    }

    /**
     * Tests for the plugin returning a response when it can't find any
     * suggestions for a word.
     *
     * @return void
     */
    public function testRespondsWithoutSuggestions()
    {
        $word = 'kjlfljlkjljkljlj';
        $this->initializeSpellCheckEvent($word);
        $response = $this->nick
            . ': I could not find any suggestions for "' . $word . '".';
        $this->checkForSpellCheckResponse($word, $response);
    }

    /**
     * Tests for the plugin returning a response when it is able to find
     * suggestions for a word.
     *
     * @return void
     */
    public function testRespondsWithSuggestions()
    {
        $word = 'teh';
        $this->initializeSpellCheckEvent($word);
        $response = $this->nick . ': Suggestions for "'
            . $word . '": the, Te, tech, Th, eh.';
        $this->checkForSpellCheckResponse($word, $response);
    }
}
