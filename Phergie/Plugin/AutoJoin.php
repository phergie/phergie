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
 * @package   Phergie_Plugin_AutoJoin
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2010 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Plugin_AutoJoin
 */

/**
 * Automates the process of having the bot join one or more channels upon
 * connection to the server.
 *
 * The configuration setting autojoin.channels is used to determine which 
 * channels to join. This setting can point to a comma-delimited string or 
 * enumerated array containing a single list of channels or an associative 
 * array keyed by hostname where each value is a comma-delimited string or  
 * enumerated array containing a list of channels to join on the server  
 * corresponding to that hostname.
 *
 * @category Phergie 
 * @package  Phergie_Plugin_AutoJoin
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Plugin_AutoJoin
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
        switch ($this->getEvent()->getCode()) {
        case Phergie_Event_Response::RPL_ENDOFMOTD:
        case Phergie_Event_Response::ERR_NOMOTD:
            if ($channels = $this->config['autojoin.channels']) {
                if (is_array($channels)) {
                    // Support autojoin.channels being in these formats:
                    // 'hostname' => array('#channel1', '#channel2', ... )
                    $host = $this->getConnection()->getHost();
                    if (isset($channels[$host])) {
                        $channels = $channels[$host];
                    }
                    if (is_array($channels)) {
                        $channels = implode(',', $channels);
                    }
                }
                $this->doJoin($channels);
            }
            $this->getPluginHandler()->removePlugin($this);
        }
    }
}
