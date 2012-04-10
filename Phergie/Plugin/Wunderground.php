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
 * @package   Phergie_Plugin_Wunderground
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2011 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Plugin_Weather
 */

/**
 * Detects and responds to requests for current weather conditions in a
 * particular location using data from a web service. Requires registering
 * with wunderground.com to obtain an api key, which must be
 * stored in the configuration settings wunderground.api_key for the plugin to 
 * function.
 *
 * @category Phergie
 * @package  Phergie_Plugin_Wunderground
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Plugin_Wunderground
 * @link     http://www.wunderground.com/weather/api/d/documentation.html
 * @uses     Phergie_Plugin_Cache pear.phergie.org
 * @uses     Phergie_Plugin_Command pear.phergie.org
 * @uses     Phergie_Plugin_Http pear.phergie.org
 * @uses     Phergie_Plugin_Temperature pear.phergie.org
 * @uses     extension SimpleXML
 */

class Phergie_Plugin_Wunderground extends Phergie_Plugin_Abstract
{
    /**
     * Makes call to wunderground's api for conditions at a particular location.
     * @param string $location 
     */
    public function onCommandWeather($location)
    {       
        $response = $this->getPluginHandler()
                ->getPlugin('Http')
                ->get(
                        'http://api.wunderground.com/api/' . 
                        $this->getConfig('wunderground.api_key') .
                        '/conditions/q/' . 
                        $location);
        
        
        $xml = new SimpleXMLElement($response->getContent());
        
        if (!$xml) {
            throw new Phergie_Exception("Error parsing XML content returned.");
        }
        
        if (isset($xml->results)) {
            throw new Phergie_Exception("That location is too ambiguous.  Please
                be more specific.");
        }
        
        if (isset($xml->error)) {
            throw new Phergie_Exception("That location was not found.");
        }
        
    }
}