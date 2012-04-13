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
 * @copyright 2008-2012 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Plugin_Wunderground
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
 * @uses     extension SimpleXML
 */

class Phergie_Plugin_Wunderground extends Phergie_Plugin_Abstract
{
    /**
     * Tracks whether or not a given location is valid
     *
     * @var bool 
     */
    private $bogusLocation = true;
    
    /**
     * Checks for dependencies
     * 
     * @return void 
     */
    
    public function onLoad()
    {
        $plugins = $this->getPluginHandler();
        $plugins->getPlugin('Cache');
        $plugins->getPlugin('Command');
        $plugins->getPlugin('Http');
        
        if (empty($this->config['wunderground.api_key'])) {
            $this->fail("API key must be specified.");
        }
    }
    
    /**
     * Makes call to wunderground's api for conditions at a particular location
     * 
     * @param string $location 
     * @return void
     */
    
    public function onCommandWeather($location)
    {       
        $response = $this->getPluginHandler()
                ->getPlugin('Http')
                ->get(
                        'http://api.wunderground.com/api/' . 
                        $this->getConfig('wunderground.api_key') .
                        '/conditions/q/' . 
                        $location . '.xml');
        
        try {
            $data = $this->parseWeatherInfo($response);
        } catch (Phergie_Exception $pe) {
            $this->doNotice($this->event->getNick(), $pe->getMessage());
        }
        
        $bits = array();
        $bits[] = 'Temperature: ' . $data['tempString'];
        $bits[] = 'Weather: ' . $data['weather'];
        $bits[] = 'Wind: ' . $data['wind_string'];
        if ('NA' != $data['wind_chill_string']) {
            $bits[] = "Wind Chill: " . $data['wind_chill_string'];
        }
        if ('NA' != $data['heat_index_string']) {
            $bits[] = "Heat Index: " . $data['heat_index_string'];
        }
        
        $string = 'Current Conditions: ' . implode(', ', $bits);
        
        $bogosity = $this->getBogusLocation();
        
        if (!$bogosity) {
            $this->doPrivmsg($this->event->getSource(),
                    $this->event->getNick(). ': ' .
                    $string);
        }
    }
    
    /**
     * Chews on reply from wunderground's API and spits out useful information
     * 
     * @param Phergie_Plugin_Http_Response $response
     * @return array Array of useful information
     * @throws Phergie_Exception 
     */
    
    public function parseWeatherInfo($response)
    {
        $xml = $response->getContent();
        
        if (isset($xml->results)) {
            $this->setBogusLocation(true);
            throw new Phergie_Exception("That location is too ambiguous.  Please be more specific.");
        }
        
        if (isset($xml->error)) {
            $this->setBogusLocation(true);
            throw new Phergie_Exception("That location was not found.");
        }
        
        $this->setBogusLocation(false);
        
        $co = $xml->current_observation;
        
        return array(
            'tempString'        => $co->temperature_string,
            'weather'           => $co->weather,
            'wind_string'       => $co->wind_string,
            'wind_chill_string' => $co->windchill_string,
            'heat_index_string' => $co->heat_index_string);
    }
    
    /**
     * @return bool 
     */
    public function getBogusLocation() 
    {
        return $this->bogusLocation;
    }

    /**
     * @param bool $bogusLocation
     * @return \Phergie_Plugin_Wunderground 
     */
    public function setBogusLocation($bogusLocation) 
    {
        $this->bogusLocation = $bogusLocation;
        return $this;
    }
}
