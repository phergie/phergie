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
 * with wunderground.com to obtain an api key, which must be stored in the
 * configuration settings wunderground.api_key for the plugin to function. Get
 * your key at http://api.wunderground.com/weather/api/.
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
            $this->fail("API key must be specified.  Use the settings index 'wunderground.api_key'.");
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
        $urlString = 'http://api.wunderground.com/api/' . 
                        $this->getConfig('wunderground.api_key') .
                        '/conditions/q/' . 
                        rawurlencode($location) . '.xml';
        
        $response = $this->getPluginHandler()
                ->getPlugin('Http')
                ->get($urlString);
        
        try {
            $data = $this->parseWeatherInfo($response);
        } catch (Phergie_Exception $pe) {
            $this->doNotice($this->event->getNick(), $pe->getMessage());
        }

        $bits = array();
        $bits[] = 'Location: ' . $location;
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
            'heat_index_string' => $co->heat_index_string
        );
    }
    
    public function onCommandForecast($location) 
    {
        $urlString = 'http://api.wunderground.com/api/' .
            $this->getConfig('wunderground.api_key') .
            '/forecast/q/' .
            rawurlencode($location) . '.xml';
        
        $response = $this->getPluginHandler()
            ->getPlugin('Http')
            ->get($urlString);
        
        $forecastData = $this->parseForecastInfo($response);
        
        $string = $this->assembleForecastString($forecastData);
        
        if (!$this->getBogusLocation()) {
            $this->doPrivmsg($this->event->getSource(),
                    $this->event->getNick(). ': ' .
                    $string);
        }
    }
    
    public function assembleForecastString($forecastData, $time = '24')
    {
        $periods = round($time / 12); // number of 12 hour periods

        $string = '';
        for ($i = 0; $i < $periods; $i++) {
            $string .= $forecastData[$i]['title'] . ': ' .
                $forecastData[$i]['forecast_text'] . ' ';
            echo $i;
        }
        return rtrim($string); // get rid of last space.  yes, it's anal retentive.
    }
    
    public function parseForecastInfo($response)
    {
        $xml = $response->getContent($response);
        
        $this->setBogusLocation(FALSE);
        
        if (isset($xml->error)) {
            $this->setBogusLocation(TRUE);
            $error_message = $xml->error->description;
            throw new Phergie_Exception('Wunderground API returned an error: ' 
                . $error_message);
        }
        
        if (isset($xml->results)) {
            $this->setBogusLocation(TRUE);
            throw new Phergie_Exception('That query was too ambiguous, try again.');
        }
        
        $forecastSet = $xml->forecast->txt_forecast->forecastdays;
        
        $forecastDays = $forecastSet->children();
        
        $forecastData = array();
        foreach ($forecastDays as $day)
        {
            if ('forecastday' == $day->getName()) {
                $forecastData[] = array(
                    'title' => $day->title,
                    'forecast_text' => $day->fcttext,
                );
            }
        }
        return $forecastData;
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
