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
 * @copyright 2008-2010 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Tests
 */

/**
 * Unit test suite for Phergie_Plugin_Ping.
 *
 * @category Phergie
 * @package  Phergie_Tests
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Tests
 */
class Phergie_Plugin_WeatherTest extends Phergie_Plugin_TestCase
{
    /**
     * XML string returned from Mock Http object
     * @var string
     */
    private $_data;

    /**
     * Result of temperature conversion returned from Temperature
     * Mock object
     * @var int
     */
    private $_temperature;

    /**
     * Expected weather report
     * @var string
     */
    private $_weatherReport = 'nick: Weather for Atlanta, GA - Temperature: 51F/10.5C, Humidity: 96%, Conditions: Fog, Updated: 3/27/11 12:52 PM EDT [ http://weather.com/weather/today/USGA0028]';

    /**
     * Mock a HTTP Plugin and prime it with response data
     *
     * @return void
     */	
    public function setUpWeatherResponse()
    {
        $this->setConfig('weather.partner_id', '1111');
        $this->setConfig('weather.license_key', '1111');

        $response1 = $this->getMock('Phergie_Plugin_Http_Response');

        $response1->expects($this->any())
            ->method('isError')
            ->will($this->returnValue(false));

        $response1->expects($this->any())
            ->method('getContent')
            ->will($this->returnValue(simplexml_load_file(dirname(__FILE__) . '/Weather/_files/location.xml')));

        $response2 = $this->getMock('Phergie_Plugin_Http_Response');

        $response2->expects($this->any())
            ->method('isError')
            ->will($this->returnValue(false));

        $response2->expects($this->any())
            ->method('getContent')
            ->will($this->returnValue(simplexml_load_file(dirname(__FILE__) . '/Weather/_files/conditions.xml')));

        $this->_data = $this->getMock("Phergie_Plugin_Http", array('get'));

        $this->_data->expects($this->any())
            ->method("get")
            ->will($this->onConsecutiveCalls($response1, $response2));

        $this->_temperature = $this->getMock('Phergie_Plugin_Temperature');
        $this->_temperature->expects($this->any())
            ->method('convertFahrenheitToCelsius')
            ->will($this->returnValue(10.5));

        $this->getMockPluginHandler()
            ->expects($this->any())
            ->method('getPlugin')
            ->will($this->returnCallback(array($this,'callback')));
    }

    /**
     * Ensure correct return value on consecutive calls to getPlugin
     * function of mock PluginHandler object
     *
     * @return mixed mock data
     */
    public function callback()
    {
        $args = func_get_args();

        if ($args[0] == 'Http') {
            return $this->_data;
        } else {
            return $this->_temperature;
        }
    }

    /**
     * Tests plugin requires Command plugin as dependency
     *
     * @return void
     */
    public function testRequiresCommandPlugin()
    {
        $this->setConfig('weather.partner_id', '1111');
        $this->setConfig('weather.license_key', '1111');

        $this->assertRequiresPlugin('Command');
        $this->plugin->onLoad();
    }

    /**
     *  Tests plugin fails if no weather partner id or license key provided
     *
     *  @return void
     */
    public function testNoConfig()
    {
        try
        {
            $this->plugin->onLoad();
            self::fail("Exception should have been thrown");
        }
        catch( Exception $e)
        {
            $this->assertInstanceOf('Phergie_Plugin_Exception', $e);
        }
    }

    /**
     * Tests output of Weather command
     *
     * @return void
     */
    public function testGetWeatherReport()
    {
        $this->setUpWeatherResponse();

        $event = $this->getMockEvent('weathercommand');
        $this->plugin->setEvent($event);

        $this->assertEmitsEvent(
            'privmsg', 
            array($this->source, 
            $this->_weatherReport)
        );

        $report = $this->plugin->onCommandWeather('atlanta');
    }

    /**
     *  Tests weather data returned
     *
     *  @return void
     */
    public function testGetWeatherData()
    {
        $this->setUpWeatherResponse();

        $weatherData = $this->plugin->getWeatherData('atlanta');

        $this->assertEquals($weatherData['temp'], 51);
    }
}
