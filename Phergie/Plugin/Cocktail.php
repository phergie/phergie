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
 * @package   Phergie_Plugin_Cocktail
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2011 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Plugin_Cocktail
 */

/**
 * Processes requests to serve users cocktail.
 *
 * @category Phergie
 * @package  Phergie_Plugin_Cocktail
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Plugin_Cocktail
 * @uses     Phergie_Plugin_Command pear.phergie.org
 * @uses     Phergie_Plugin_Serve pear.phergie.org
 */
class Phergie_Plugin_Cocktail extends Phergie_Plugin_Abstract
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
     * Processes requests to serve a user a cocktail.
     *
     * @param string $request Request including the target and an optional
     *        suggestion of what cocktail to serve
     *
     * @return void
     */
    public function onCommandCocktail($request)
    {
        $format = $this->getConfig(
            'cocktail.format',
            'throws %target% %article% %item%.'
        );

        $this->plugins->getPlugin('Serve')->serve(
            $this->findDataFile('cocktail.db'),
            'cocktail',
            $format,
            $request,
            true
        );
    }
}

