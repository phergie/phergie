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
 * @copyright 2008-2010 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Plugin_Php
 */

/**
 * Shortens urls via the tr.im service
 *
 * @category Phergie 
 * @package  Phergie_Plugin_Url
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Plugin_Url
 */
class Phergie_Plugin_Url_Shorten_Trim extends Phergie_Plugin_Url_Shorten_Abstract
{
    /**
     * Short a URL through the tr.im api
     *
     * @param string $url the url to shorten
     *
     * @return string string the shortened url
     */
    public function shorten($url)
    {
        return file_get_contents('http://api.tr.im/v1/trim_simple?url=' . rawurlencode($url));
    }
}
