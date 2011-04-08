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
 * @package   Phergie_Plugin_Beer
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2011 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Plugin_Beer
 */

/**
 * Processes requests to serve users beer.
 *
 * @category Phergie
 * @package  Phergie_Plugin_Beer
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Plugin_Beer
 * @uses     Phergie_Plugin_Command pear.phergie.org
 * @uses     Phergie_Plugin_Serve pear.phergie.org
 */
class Phergie_Plugin_Beer extends Phergie_Plugin_Abstract
{
    /**
     * Checks for dependencies.
     *
     * @return void
     */
    public function onLoad()
    {
        $plugins = $this->plugins;
        $plugins->getPlugin('Command');
        $plugins->getPlugin('Serve');
    }

    /**
     * Processes requests to serve a user a beer.
     *
     * @param string $request Request including the target and an optional
     *        suggestion of what beer to serve
     *
     * @return void
     */
    public function onCommandBeer($request)
    {
        $format = $this->getConfig(
            'beer.format',
            'throws %target% %article% %item%.'
        );

        $this->plugins->getPlugin('Serve')->serve(
            dirname(__FILE__) . '/Beer/beer.db',
            'beer',
            $format,
            $request
        );
    }

    /**
     * Adds a "booze" alias for the "beer" command.
     *
     * @param string $request Request including the target and an optional
     *        suggestion of what beer to serve
     *
     * @return void
     */
    public function onCommandBooze($request)
    {
        $this->onCommandBeer($request);
    }
}
