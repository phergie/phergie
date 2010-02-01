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
 * @package   Phergie_Core
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2010 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Core
 */

/**
 * Handles switching to alternate nicks in cases where the primary nick is 
 * not available for use.
 *
 * @category Phergie 
 * @package  Phergie_Core
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Core
 */
class Phergie_Plugin_AltNick extends Phergie_Plugin_Abstract
{
    /**
     * Iterator for the alternate nick list
     *
     * @var ArrayIterator 
     */
    protected $iterator;

    /**
     * Initializes instance variables.
     *
     * @return void
     */
    public function onConnect()
    {
        if (!empty($this->_config['altnick.nicks'])) {
            if (is_string($this->_config['altnick.nicks'])) {
                $this->_config['altnick.nicks'] 
                    = array($this->_config['altnick.nicks']);
            }
            $this->iterator = new ArrayIterator($this->_config['altnick.nicks']);
        }
    }

    /**
     * Switches to alternate nicks as needed when nick collisions occur.
     *
     * @return void
     */
    public function onResponse()
    {
        // If no alternate nick list was found, return
        if (empty($this->iterator)) {
            return;
        }

        // If the response event indicates that the nick set is in use...
        $code = $this->getEvent()->getCode();
        if ($code == Phergie_Event_Response::ERR_NICKNAMEINUSE) {

            // Attempt to move to the next nick in the alternate nick list
            $this->iterator->next();

            // If another nick is available...
            if ($this->iterator->valid()) {
                
                // Switch to the new nick
                $altNick = $this->iterator->current();
                $this->doNick($altNick);

                // Update the connection to reflect the nick change
                $this->getConnection()->setNick($altNick);

            } else {
                // If no other nicks are available...

                // Terminate the connection
                $this->doQuit('All specified alternate nicks are in use');
            }
        }
    }
}
