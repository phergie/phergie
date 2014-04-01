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
 * @package   Phergie_Tests
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2012 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Tests
 */

/**
 * Unit test suite for Phergie_Plugin_Temperature.
 *
 * @category Phergie
 * @package  Phergie_Tests
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Tests
 */
class Phergie_Plugin_TemperatureTest extends Phergie_Plugin_TestCase
{
    /**
     * Test if the conversion from Celsius to Fahrenheit is done correctly
     *
     * @dataProvider dataProviderCelsiusFahrenheit
     */
    public function testConversionFromCelsiusToFahrenheit($celsius, $fahrenheit)
    {
        $this->assertEquals($fahrenheit, $this->plugin->convertCelsiusToFahrenheit($celsius));
    }

    /**
     * Test if the conversion from Fahrenheit to Celcius is done correctly
     *
     * @dataProvider dataProviderCelsiusFahrenheit
     */
    public function testConversionFromFahrenheitToCelcius($celsius, $fahrenheit)
    {
        $this->assertEquals($celsius, $this->plugin->convertFahrenheitToCelsius($fahrenheit));
    }

    /**
     * Returns a temperature table in Celcius and Fahrenheit
     *
     * @return array
     */
    public function dataProviderCelsiusFahrenheit()
    {
        return array(
            array(  0,  32),
            array( 10,  50),
            array(-10,  14),
            array( 32,  90),
            array(-32, -26),
        );
    }

    /**
     * Testing the heat index formula
     *
     * @dataProvider dataProviderHeatIndex
     */
    public function testHeadIndex($expected, $fahrenheit, $humidity)
    {
        $this->assertEquals($expected, $this->plugin->getHeatIndex($fahrenheit, $humidity));
    }

    /**
     * Returns a table with expected heat index, temperature in Fahrenheit and humidity
     *
     * @return array
     */
    public function dataProviderHeatIndex()
    {
        return array(
            array(129, 100,  60),
            array(84,   80,  80),
            array(87,   80, 100),
        );
    }
}
