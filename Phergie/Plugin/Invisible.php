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
 * @package   Phergie_Plugin_Invisible
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2012 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Plugin_Invisible
 */

/**
 * Marks the bot as invisible when it connects to the server.
 *
 * @category Phergie
 * @package  Phergie_Plugin_Invisible
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Plugin_Invisible
 * @link     http://irchelp.org/irchelp/rfc/chapter4.html#c4_2_3_2
 */
class Phergie_Plugin_Invisible extends Phergie_Plugin_Abstract
{
    /**
     * Marks the bot as invisible when it connects to the server.
     *
     * @return void
     */
    public function onConnect()
    {
        $this->doMode($this->getConnection()->getNick(), '+i');
        $this->getPluginHandler()->removePlugin($this);
    }
}
