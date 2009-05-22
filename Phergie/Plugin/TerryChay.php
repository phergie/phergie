<?php

/**
 * Parses incoming messages for the words "Terry Chay" or tychay and responds
 *  with a random Terry fact retrieved from Sean's Chayism service. 
 */
class Phergie_Plugin_TerryChay extends Phergie_Plugin_Abstract
{

    /**
     * URL to the Chayism service
     *
     * @var string
     */
    protected $chayismURL = 'http://phpdoc.info/chayism/';

    /**
     * Fetches a chayism
     *
     * @return bool True is successful, else false
     */
    private function getChayism()
    {
        return file_get_contents($this->chayismURL);
    }

    /**
     * Parses incoming messages for "Terry Chay"|tychay and respond with a
     * chayism
     *
     * @return void
     */
    public function onPrivmsg()
    {
        $source = $this->_event->getSource();
        $message = $this->_event->getArgument(1);

        // Check to see if the message includes Terry Chay.
        if (preg_match('{^(' . 
                preg_quote($this->_config['command.prefix']) . 
                '\s*)?.*(terry\s+chay|tychay)}ix', $message, $m)) {
            $fact = $this->getChayism();
            if (!empty($fact)) {
                $this->doPrivmsg($source, 'Fact: ' . $fact);
                if ($source[0] == '#') {
                    $this->floodCache[$source] = time();
                }
                unset($m, $fact);
            }
        }
    }

    /**
     * Parses incoming CTCP request for "Terry Chay"|tychay and respond with a
     * chayism
     *
     * @return void
     */
    public function onCtcp()
    {
        $source = $this->_event->getSource();
        $ctcp = $this->_event->getArgument(1);

        if (preg_match('({terry[\s_+-]*chay}|tychay)ix', $ctcp, $m)) {
            $fact = $this->getChayism();
            if (!empty($fact)) {
                $this->doCtcpReply($source, 'TERRYCHAY', $fact);
            }
        }
    }
}
