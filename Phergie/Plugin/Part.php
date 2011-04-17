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
 * @package   Phergie_Plugin_Part
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2011 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Plugin_Part
 */

/**
 * Parts a specified channel on command from a user.
 *
 * @category Phergie 
 * @package  Phergie_Plugin_Part
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Plugin_Part
 * @uses     Phergie_Plugin_Command pear.phergie.org
 */
class Phergie_Plugin_Part extends Phergie_Plugin_Abstract
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
     * Parts a channel.
     *
     * @param string $channels Comma-delimited list of channels to leave
     *
     * @return void
     */
    public function onCommandPart($channels)
    {
        $this->doPart($channels);
    }
}
