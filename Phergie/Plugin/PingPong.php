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
 * Responds with "$nick: pong" when some wrote "ping".
 * Additionally responses with "$nick: passed" when someone wrote "test".
 *
 * @category Phergie
 * @package  Phergie_Plugin_PingPong
 * @author   Marcel Glacki <Marcel.Glacki@stud.fh-swf.de>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Plugin_Pong
 * @link     http://irchelp.org/irchelp/rfc/chapter4.html#c4_6_2
 * @link     http://irchelp.org/irchelp/rfc/chapter4.html#c4_6_3
 */
class Phergie_Plugin_PingPong extends Phergie_Plugin_Abstract
{
  /**
   * Responds to "ping" chatmessages.
   *
   * @return void
   */
  public function onCommandPing( $args = NULL )
  {
    // Respond to "ping" only
    // but not when a message starts with or contains "ping"
    if( is_null( $args ))
      $this->doPrivmsg( $this->event->getSource(),
        $this->event->getNick().": pong" );
  }
  /**
   * Responds to "test" chatmessages.
   *
   * @return void
   */
  public function onCommandTest( $args = NULL )
  {
    // Respond to "test" only
    // but not when a message starts with or contains "test"
    if( is_null( $args ))
      $this->doPrivmsg( $this->event->getSource(),
        $this->event->getNick().": passed" );
  }
}
