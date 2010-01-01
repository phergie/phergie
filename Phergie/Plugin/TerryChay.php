<?php

/**
 * Parses incoming messages for the words "Terry Chay" or tychay and responds
 *  with a random Terry fact retrieved from Sean's Chayism service. 
 */
class Phergie_Plugin_TerryChay extends Phergie_Plugin_Abstract
{
    /**
     * Mapping of plugin request source names to previous request times, 
     * used to prevent a user from flooding the plugin with requests
     *
     * @var array
     */
    protected $_floodCache = array();

    /**
     * URL to the web service
     *
     * @var string
     */
    protected $_url = 'http://phpdoc.info/chayism/';

    /**
     * Fetches a chayism.
     *
     * @return bool TRUE if successful, FALSE otherwise 
     */
    public function getChayism()
    {
        return file_get_contents($this->_url);
    }

    /**
     * Parses incoming messages for "Terry Chay"|tychay and respond with a
     * chayism.
     *
     * @return void
     */
    public function onPrivmsg()
    {
        $event = $this->getEvent();
        $source = $event->getSource();
        $message = $event->getText();

        // Check to see if the message includes Terry Chay.
        if (preg_match('{^(' . 
                preg_quote($this->_config['command.prefix']) . 
                '\s*)?.*(terry\s+chay|tychay)}ix', $message, $m)) {
            $fact = $this->getChayism();
            if (!empty($fact)) {
                $this->doPrivmsg($source, 'Fact: ' . $fact);
                if ($source[0] == '#') {
                    $this->_floodCache[$source] = time();
                }
            }
        }
    }

    /**
     * Parses incoming CTCP request for "Terry Chay"|tychay and respond with a
     * chayism.
     *
     * @return void
     */
    public function onCtcp()
    {
        $event = $this->getEvent();
        $source = $event->getSource();
        $ctcp = $event->getArgument(1);

        if (preg_match('({terry[\s_+-]*chay}|tychay)ix', $ctcp, $m)) {
            $fact = $this->getChayism();
            if (!empty($fact)) {
                $this->doCtcpReply($source, 'TERRYCHAY', $fact);
            }
        }
    }
}
