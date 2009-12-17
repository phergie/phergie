<?php

/**
 * Handles switching to alternate nicks in cases where the primary nick is 
 * not available for use.
 */
class Phergie_Plugin_AltNick extends Phergie_Plugin_Abstract
{
    /**
     * Iterator for the alternate nick list
     *
     * @var ArrayIterator 
     */
    protected $_iterator;

    /**
     * Initializes instance variables.
     *
     * @return void
     */
    public function onConnect()
    {
        if (!empty($this->_config['altnick.nicks'])) {
            if (is_string($this->_config['altnick.nicks'])) {
                $this->_config['altnick.nicks'] = array($this->_config['altnick.nicks']);
            }
            $this->_iterator = new ArrayIterator($this->_config['altnick.nicks']);
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
        if (empty($this->_iterator)) {
            return;
        }

        // If the response event indicates that the nick set is in use...
        if ($this->getEvent()->getCode() == Phergie_Event_Response::ERR_NICKNAMEINUSE) {

            // Attempt to move to the next nick in the alternate nick list
            $this->_iterator->next();

            // If another nick is available...
            if ($this->_iterator->valid()) {
                
                // Switch to the new nick
                $altNick = $this->_iterator->current();
                $this->doNick($altNick);

                // Update the connection to reflect the nick change
                $this->getConnection()->setNick($altNick);

            // If no other nicks are available...
            } else {

                // Terminate the connection
                $this->doQuit('All specified alternate nicks are in use');
            }
        }
    }
}
