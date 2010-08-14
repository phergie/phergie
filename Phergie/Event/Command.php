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
 * Event originating from a plugin for the bot.
 *
 * @category Phergie
 * @package  Phergie
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie
 */
class Phergie_Event_Command extends Phergie_Event_Request
{
    /**
     * Reference to the plugin instance that created the event
     *
     * @var Phergie_Plugin_Abstract
     */
    protected $plugin;

    /**
     * Stores a reference to the plugin instance that created the event.
     *
     * @param Phergie_Plugin_Abstract $plugin Plugin instance
     *
     * @return Phergie_Event_Command Provides a fluent interface
     */
    public function setPlugin(Phergie_Plugin_Abstract $plugin)
    {
        $this->plugin = $plugin;
        return $this;
    }

    /**
     * Returns a reference to the plugin instance that created the event.
     *
     * @return Phergie_Plugin_Abstract|null Plugin instance or NULL if none
     *         has been set
     */
    public function getPlugin()
    {
        return $this->plugin;
    }
}
