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
 * @package   Phergie_Plugin_Cookie
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2010 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Plugin_Cookie
 */

/**
 * Processes requests to serve users cookies.
 *
 * @category Phergie
 * @package  Phergie_Plugin_Cookie
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Plugin_Cookie
 * @uses     Phergie_Plugin_Command pear.phergie.org
 * @uses     Phergie_Plugin_Serve pear.phergie.org
 */
class Phergie_Plugin_Cookie extends Phergie_Plugin_Abstract
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
     * Processes requests to serve a user a cookie.
     *
     * @param string $request Request including the target and an optional
     *        suggestion of what cookie to serve
     *
     * @return void
     */
    public function onCommandCookie($request)
    {
        $format = $this->getConfig(
            'cookie.format',
            'throws %target% %article% %item%.'
        );

        $this->plugins->getPlugin('Serve')->serve(
            dirname(__FILE__) . '/Cookie/cookie.db',
            'cookies',
            $format,
            $request
        );
    }
}
