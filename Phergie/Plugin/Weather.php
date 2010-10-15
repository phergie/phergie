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
            $this->fail(
                'weather.partner_id and weather.license_key must be specified'
            );
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
        $response = $this->plugins->http->get(
            'http://xoap.weather.com/search/search',
            array('where' => $location)
        );

        if ($response->isError()) {
            $this->doNotice(
                $this->event->getNick(),
                'ERROR: ' . $response->getCode() . ' ' . $response->getMessage()
            );
            return;
        }

        $nick = $this->event->getNick();

        $xml = $response->getContent();
        if (count($xml->loc) == 0) {
            $this->doNotice($nick, 'No results for that location.');
            return;
        }

        $where = (string) $xml->loc[0]['id'];
        $response = $this->plugins->http->get(
            'http://xoap.weather.com/weather/local/' . $where,
            array(
                'cc' => '*',
                'link' => 'xoap',
                'prod' => 'xoap',
                'par' => $this->config['weather.partner_id'],
                'key' => $this->config['weather.license_key'],
            )
        );

        if ($response->isError()) {
            $this->doNotice(
                $this->event->getNick(),
                'ERROR: ' . $response->getCode() . ' ' . $response->getMessage()
            );
            return;
        }

        $temperature = $this->plugins->getPlugin('Temperature');
        $xml = $response->getContent();
        $weather = 'Weather for ' . (string) $xml->loc->dnam . ' - ';
        switch ($xml->head->ut) {
            case 'F':
                $tempF = $xml->cc->tmp;
                $tempC = $temperature->convertFahrenheitToCelsius($tempF);
                break;
            case 'C':
                $tempC = $xml->cc->tmp;
                $tempF = $temperature->convertCelsiusToFahrenheit($tempC);
                break;
            default:
                $this->doNotice(
                    $this->event->getNick(),
                    'ERROR: No scale information given.'
                );
                break;
        }
        $r = $xml->cc->hmid;
        $hiF = $temperature->getHeatIndex($tempF, $r);
        $hiC = $temperature->convertFahrenheitToCelsius($hiF);
        $weather .= 'Temperature: ' . $tempF . 'F/' . $tempC . 'C';
        $weather .= ', Humidity: ' . (string) $xml->cc->hmid . '%';
        if ($hiF > $tempF || $hiC > $tempC) {
            $weather .= ', Heat Index: ' . $hiF . 'F/' . $hiC . 'C';
        }
        $weather .=
            ', Conditions: ' . (string) $xml->cc->t .
            ', Updated: ' . (string) $xml->cc->lsup .
            ' [ http://weather.com/weather/today/' .
            str_replace(
                array('(', ')', ',', ' '),
                array('', '', '', '+'),
                (string) $xml->loc->dnam
            ) .
            ' ]';

        $this->doPrivmsg($this->event->getSource(), $nick . ': ' . $weather);
    }
}
