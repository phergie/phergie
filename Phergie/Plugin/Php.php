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
 * @package   Phergie_Plugin_Php
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2011 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Plugin_Php
 */

/**
 * Returns information on PHP functions as requested. 
 *
 * @category Phergie 
 * @package  Phergie_Plugin_Php
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Plugin_Php
 * @uses     extension pdo 
 * @uses     extension pdo_sqlite 
 * @uses     Phergie_Plugin_Command pear.phergie.org
 */
class Phergie_Plugin_Php extends Phergie_Plugin_Abstract
{
    /**
     * Data source to use
     *
     * @var Phergie_Plugin_Php_Source
     */
    protected $source;

    /**
     * Check for dependencies.
     *
     * @return void
     */
    public function onLoad()
    {
        // @todo find a way to move this to Phergie_Plugin_Php_Source_Local
        if (!extension_loaded('PDO') || !extension_loaded('pdo_sqlite')) {
            $this->fail('PDO and pdo_sqlite extensions must be installed');
        }

        $this->getPluginHandler()->getPlugin('Command');

        try {
            $this->source = new Phergie_Plugin_Php_Source_Local;
        } catch (Phergie_Plugin_Source_Local_Exception $e) {
            $this->fail($e->getMessage());
        }
    }

    /**
     * Searches the data source for the requested function.
     * 
     * @param string $functionName Name of the function to search for
     *
     * @return void
     */
    public function onCommandPhp($functionName)
    {
        $nick = $this->event->getNick();
        if ($function = $this->source->findFunction($functionName)) {
            $msg = $nick . ': ' . $function['description'];
            $this->doPrivmsg($this->event->getSource(), $msg);
        } else {
            $msg = 'Search for function ' . $functionName . ' returned no results.';
            $this->doNotice($nick, $msg);
        }
    }
}
