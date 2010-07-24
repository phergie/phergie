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
 * @package   Phergie_Plugin_Temperature
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2010 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Plugin_Temperature
 */

/**
 * Performs temperature calculations for other plugins.
 *
 * @category Phergie
 * @package  Phergie_Plugin_Temperature
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Plugin_Temperature
 */
class Phergie_Plugin_Temperature extends Phergie_Plugin_Abstract
{
    /**
     * Converts a temperature in Celsius to Fahrenheit.
     *
     * @param int $temp Temperature in Celsius
     *
     * @return int Temperature converted to Fahrenheit
     */
    public function convertCelsiusToFahrenheit($temp)
    {
        return round(((((int) $temp * 9) / 5) + 32));
    }

    /**
     * Converts a temperature in Fahrenheit to Celsius.
     *
     * @param int $temp Temperature in Fahrenheit
     *
     * @return int Temperature converted to Celsius
     */
    public function convertFahrenheitToCelsius($temp)
    {
        return round(((((int) $temp - 32) * 5) / 9));
    }

    /**
     * Calculates the heat index (i.e. "feels like" temperature) based on
     * temperature and relative humidity.
     *
     * @param int $temperature Temperature in degrees Fahrenheit
     * @param int $humidity Relative humidity (ex: 68)
     * @return int Heat index in degrees Fahrenheit
     */
    public function getHeatIndex($temperature, $humidity)
    {
        $temperature2 = $temperature * $temperature;
        $humidity2 = $humidity * $humidity;
        return round(
            -42.379 +
            (2.04901523 * $temperature) +
            (10.14333127 * $humidity) -
            (0.22475541 * $temperature * $humidity) -
            (0.00683783 * $temperature2) -
            (0.05481717 * $humidity2) +
            (0.00122874 * $temperature2 * $humidity) +
            (0.00085282 * $temperature * $humidity2) -
            (0.00000199 * $temperature2 * $humidity2)
        );
    }
}
