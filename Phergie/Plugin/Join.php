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
 * @package   Phergie_Plugin_Join
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2011 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Plugin_Join
 */

/**
 * Joins a specified channel on command from a user.
 *
 * @category Phergie
 * @package  Phergie_Plugin_Join
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Plugin_Join
 * @uses     Phergie_Plugin_Command pear.phergie.org
 */
class Phergie_Plugin_Join extends Phergie_Plugin_Abstract
{
    /**
     * Checks for dependencies.
     *
     * @return void
     */
    public function onLoad()
    {
        $this->getPluginHandler()->getPlugin('Command');
    }

    /**
     * Joins a channel.
     *
     * @param string $channels Comma-delimited list of channels to join
     * @param string $keys     Optional comma-delimited list of channel keys
     *
     * @return void
     */
    public function onCommandJoin($channels, $keys = null)
    {
        if ($keys) {
            $this->doJoin($channels, $keys);
        } else {
            $this->doJoin($channels);
        }
    }
}
