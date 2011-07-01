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
 * @package   Phergie_Plugin_Pong
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2011 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Plugin_Pong
 */

/**
 * Responds to PING requests from the server.
 *
 * @category Phergie 
 * @package  Phergie_Plugin_Pong
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Plugin_Pong
 * @link     http://irchelp.org/irchelp/rfc/chapter4.html#c4_6_2
 * @link     http://irchelp.org/irchelp/rfc/chapter4.html#c4_6_3
 */
class Phergie_Plugin_Pong extends Phergie_Plugin_Abstract
{
    /**
     * Sends a PONG response for each PING request received by the server. 
     *
     * @return void
     */
    public function onPing()
    {
        $this->doPong($this->getEvent()->getArgument(0));
    }
}
