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
 * @package   Phergie_Plugin_TerryChay
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2011 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Plugin_TerryChay
 */

/**
 * Parses incoming messages for the words "Terry Chay" or tychay and responds
 * with a random Terry fact retrieved from the Chayism web service.
 *
 * @category Phergie
 * @package  Phergie_Plugin_TerryChay
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Plugin_TerryChay
 * @uses     Phergie_Plugin_Http pear.phergie.org
 */
class Phergie_Plugin_TerryChay extends Phergie_Plugin_Abstract
{
    /**
     * URL to the web service
     *
     * @const string
     */
    const URL = 'http://phpdoc.info/chayism/';

    /**
     * HTTP plugin
     *
     * @var Phergie_Plugin_Http
     */
    protected $http;

    /**
     * Checks for dependencies.
     *
     * @return void
     */
    public function onLoad()
    {
        $this->getPluginHandler()->getPlugin('Http');
    }

    /**
     * Fetches a chayism.
     *
     * @return string|bool Fetched chayism or FALSE if the operation failed
     */
    public function getChayism()
    {
        return $this
            ->getPluginHandler()
            ->getPlugin('Http')
            ->get(self::URL)
            ->getContent();
    }

    /**
     * Parses incoming messages for "Terry Chay" and related variations and
     * responds with a chayism.
     *
     * @return void
     */
    public function onPrivmsg()
    {
        $event = $this->getEvent();
        $source = $event->getSource();
        $message = $event->getText();
        $pattern
            = '{^(' . preg_quote($this->getConfig('command.prefix')) .
            '\s*)?.*(terry\s+chay|tychay)}ix';

        if (preg_match($pattern, $message)) {
            if ($fact = $this->getChayism()) {
                $this->doPrivmsg($source, 'Fact: ' . $fact);
            }
        }
    }
}
