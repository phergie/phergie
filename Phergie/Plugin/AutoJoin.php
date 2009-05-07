<?php

/**
 * Automates the process of having the bot join one or more channels upon
 * connection to the server.
 */
class Phergie_Plugin_AutoJoin extends Phergie_Plugin_Abstract
{
    /**
     * Intercepts the end of the "message of the day" response and responds by
     * joining the channels specified in the configuration file.
     *
     * @return void
     */
    public function onResponse()
    {
        switch ($this->_event->getCode()) {
            case Phergie_Event_Response::RPL_ENDOFMOTD:
            case Phergie_Event_Response::ERR_NOMOTD:
                $channels = $this->_config['autojoin.channels'];
                if (!empty($channels)) {
                    if (is_array($channels)) {
                        $channels = implode(',', $channels);
                    }
                    $this->doJoin($channels);
                }
        }
    }
}
