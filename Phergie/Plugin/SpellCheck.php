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
 * @package   Phergie_Plugin_TerryChay
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2010 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Plugin_TerryChay
 */

/**
 * Handles requests for checking spelling of specified words and returning
 * either confirmation of correctly spelled words or potential correct
 * spellings for misspelled words.
 *
 * @category Phergie 
 * @package  Phergie_Plugin_SpellCheck
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Plugin_TerryChay
 * @uses     Phergie_Plugin_Command pear.phergie.org
 * @uses     extension pspell
 */
class Phergie_Plugin_SpellCheck extends Phergie_Plugin_Abstract
{

    /**
     * Spell check dictionary handler
     *
     * @var resource
     */
    protected $pspell;

    /**
     * Limit on the number of potential correct spellings returned
     *
     * @var int
     */
    protected $limit;

    /**
     * Check for dependencies.
     *
     * @return void
     */
    public function onLoad()
    {
        if (!extension_loaded('pspell')) {
            $this->fail('pspell php extension is required');
        }

        if (!$this->getConfig('spellcheck.lang')) {
            $this->fail('Setting spellcheck.lang must be filled-in');
        }

        $this->plugins->getPlugin('Command');
       
        set_error_handler(array($this, 'loadDictionaryError'));
        $this->pspell = pspell_new($this->getConfig('spellcheck.lang'));
        restore_error_handler();

        $this->limit = $this->getConfig('spellcheck.limit', 5);
    }

    /**
     * Intercepts and handles requests for spell checks.
     *
     * @param string $word the string to perform checks against
     *
     * @return void
     */
    public function onCommandSpell($word)
    {
        $source = $this->event->getSource();
        $target = $this->event->getNick();

        $message  = $target . ': The word "' . $word;
        $message .= '" seems to be spelt correctly.';
        if (!pspell_check($this->pspell, $word)) {
            $suggestions = pspell_suggest($this->pspell, $word);
           
            $message  = $target; 
            $message .= ': I could not find any suggestions for "' . $word . '".';
            if (!empty($suggestions)) {
                $suggestions = array_splice($suggestions, 0, $this->limit);
                $message     = $target . ': Suggestions for "';
                $message    .= $word . '": ' . implode(', ', $suggestions) . '.';
            }
        }
         
        $this->doPrivmsg($source, $message);
    }

    /**
     * Handle any errors from loading dictionary
     *
     * @param integer $errno   Error code
     * @param string  $errstr  Error message
     * @param string  $errfile File that errored
     * @param integer $errline Line where the error happened
     *
     * @return void
     */
    protected function loadDictionaryError($errno, $errstr, $errfile, $errline)
    {
        $this->fail($errstr);
    }

}
