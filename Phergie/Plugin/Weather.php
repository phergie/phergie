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
 * @package   Phergie_Plugin_Weather
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2010 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Plugin_Weather
 */

/**
 * Detects and responds to requests for current weather conditions in a
 * particular location using data from a web service. Requires registering
 * with weather.com to obtain authentication credentials, which must be
 * stored in the configuration settings weather.partner_id and
 * weather.license_key for the plugin to function.
 *
 * @category Phergie
 * @package  Phergie_Plugin_Weather
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Plugin_Weather
 * @link     http://www.weather.com/services/xmloap.html
 * @uses     Phergie_Plugin_Command pear.phergie.org
 * @uses     Phergie_Plugin_Http pear.phergie.org
 * @uses     Phergie_Plugin_Temperature pear.phergie.org
 * @uses     extension SimpleXML
 */
class Phergie_Plugin_Weather extends Phergie_Plugin_Abstract
{
    /**
     * Checks for dependencies.
     *
     * @return void
     */
    public function onLoad()
    {
        $plugins = $this->getPluginHandler();
        $plugins->getPlugin('Command');
        $plugins->getPlugin('Http');
        $plugins->getPlugin('Temperature');

        if (empty($this->config['weather.partner_id'])
            || empty($this->config['weather.license_key'])
        ) {
            $this->fail('weather.partner_id and weather.license_key must be specified');
        } 
    }

    /**
     * Returns a weather report for a specified location.
     *
     * @param string $location Zip code or city/state/country specification
     *
     * @return void
     */
    public function onCommandWeather($location)
    {
        try
        {
            $this->doPrivmsg($this->event->getSource(), $this->event->getNick() . ': ' . $this->getWeatherReport($location));
        }
        catch(Exception $e)
        {
            $this->doNotice($this->event->getNick(), $e->getMessage());	
        }
    }

    /**
     *  Generates a weather report for a specified location
     *
     *  @param string $location name of place to retrieve weather report for
     *
     *  @return void
     */
    protected function getWeatherReport($location)
    {
        $conditions = $this->getWeatherData($location);
 
        $report = 'Weather for ' . $conditions['cityName'] . ' - ';

        $temperature = $this->getPluginHandler()->getPlugin('Temperature');
        switch ($conditions['tempUnit']) 
        {
        case 'F':
            $tempF = $conditions['temp'];
            $tempC = $temperature->convertFahrenheitToCelsius($tempF);
            break;
        case 'C':
            $tempC = $conditions['temp'];
            $tempF = $temperature->convertCelsiusToFahrenheit($tempC);
            break;
        default:
            throw new Exception('ERROR: No scale information given.');
            break;
        }

        $hiF     = $temperature->getHeatIndex($tempF, $conditions['relativeHumidity']/100);
        $hiC     = $temperature->convertFahrenheitToCelsius($hiF);
        $report .= 'Temperature: ' . $tempF . 'F/' . $tempC . 'C';
        $report .= ', Humidity: ' . $conditions['relativeHumidity'] . '%';
        if ($hiF > $tempF || $hiC > $tempC) {
            $weather .= ', Heat Index: ' . $hiF . 'F/' . $hiC . 'C';
        }
        $report .=
            ', Conditions: ' . (string) $conditions['weatherDescriptionPhrase'] .
            ', Updated: ' . (string) $conditions['observationDateTime'] .
            ' [ http://weather.com/weather/today/' . $conditions['locationCode'] . ']';

        return $report;
    }

    /**
     * Retrieve TWCi Content
     *
     * @param string $location place to retrieve weather data for
     *
     * @return array weather conditions
     */
    public function getWeatherData($location)
    {
        $response = $this->getPluginHandler()
                         ->getPlugin('Http')
                         ->get('http://xoap.weather.com/search/search',
                                array('where' => $location));
        
        if ($response->isError()) {
            throw new Exception('ERROR: ' . $response->getMessage() . ' ' . $response->getCode());
        }

        $xml = $response->getContent();

        if (count($xml->loc) == 0) {
            throw new Exception('No results for that location');
        }

        $locId = (string) $xml->loc[0]['id'];

        $response = $this->getPluginHandler()
                         ->getPlugin('Http')
                         ->get('http://xoap.weather.com/weather/local/' . $locId,
                                array(
                                    'cc' => '*',
                                    'link' => 'xoap',
                                    'prod' => 'xoap',
                                    'par' => $this->config['weather.partner_id'],
                                    'key' => $this->config['weather.license_key'],
                                ));

        if ($response->isError()) {
            throw new Exception('ERROR: ' . $response->getMessage() . ' ' . $response->getCode());
        }

        $data = $response->getContent();
        return array(
            'locationCode'=>"{$locId}",
            'cityName'=>"{$data->loc->dnam}",
            'observationDateTime'=>"{$data->cc->lsup}",
            'observationPoint'=>"{$data->cc->obst}",
            'temp'=>"{$data->cc->tmp}",
            'feelsLikeTemp'=>"{$data->cc->flik}",
            'tempUnit'=>"{$data->head->ut}",
            'weatherDescriptionPhrase'=>"{$data->cc->t}",
            'barometricPressure'=>"{$data->cc->bar->r}",
            'barometricTrend'=>"{$data->cc->bar->d}",
            'windSpeed'=>"{$data->cc->wind->s}",
            'windGust'=>"{$data->cc->wind->gust}",
            'windDirection'=>"{$data->cc->wind->d}",
            'windDirectionPhrase'=>"{$data->cc->wind->t}",
            'relativeHumidity'=>"{$data->cc->hmid}",
            'visibility'=>"{$data->cc->vis}",
            'uvIndex'=>"{$data->cc->uv->i}",
            'uvIndexDescription'=>"{$data->cc->uv->t}",
            'dewPoint'=>"{$data->cc->dewp}",
            'sunrise'=>"{$data->loc->sunr}",
            'sunset'=>"{$data->loc->suns}",
            'moonPhaseDescription'=>"{$data->cc->moon->t}",
        );
    }
}
