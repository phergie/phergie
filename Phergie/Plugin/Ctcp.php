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
 * @package   Phergie
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2010 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie
 */

/**
 * Responds to various CTCP requests sent by the server and users.
 *
 * @category Phergie 
 * @package  Phergie
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie
 * @see      http://www.irchelp.org/irchelp/rfc/ctcpspec.html 
 */
class Phergie_Plugin_Ctcp extends Phergie_Plugin_Abstract
{
    /**
     * Responds to a CTCP TIME request from a user with the current local 
     * time.
     *
     * @return void
     */
    public function onTime()
    {
        $source = $this->getEvent()->getSource();
        $this->doTime($source, strftime('%c %z'));
    }

    /**
     * Responds to a CTCP VERSION request from a user with the codebase 
     * version.
     *
     * @return void
     */
    public function onVersion()
    {
        $source = $this->getEvent()->getSource();
        $msg = 'Phergie ' . Phergie_Bot::VERSION . ' (http://phergie.org)';
        $this->doVersion($source, $msg);
    }

    /**
     * Responds to a CTCP PING request from a user.
     *
     * @return void
     */
    public function onCtcpPing()
    {
        $event = $this->getEvent();
        $source = $event->getSource();
        $handshake = $event->getArgument(1);
        $this->doPing($source, $handshake);
    }

    /**
     * Responds to a CTCP FINGER request from a user.
     *
     * @return void
     */
    public function onFinger()
    {
        $connection = $this->getConnection();
        $name = $connection->getNick();
        $realname = $connection->getRealname();
        $username = $connection->getUsername(); 

        $finger 
            = (empty($realname) ? $realname : $name) . 
            ' (' . (!empty($username) ? $username : $name) . ')';

        $this->doFinger($source, $finger);
    }
}
?>
