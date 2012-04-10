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
 * @copyright 2008-2011 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Tests
 */

/**
 * Unit test suite for Phergie_Plugin_Weather.
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
     * XML string returned from mock Http object
     *
     * @var string
     */
    private $_data;

    /**
     * Result of temperature conversion returned from Temperature
     * mock object
     *
     * @var int
     */
    private $_temperature;

    /**
     * Mock a HTTP Plugin and prime it with response data
     *
     * @return void
     */
    public function setUpWeatherResponse($dataSet)
    {
        $this->setConfig('weather.partner_id', '1111');
        $this->setConfig('weather.license_key', '1111');

        $dir = dirname(__FILE__) . '/Weather/_files/' . $dataSet;

        $config = require $dir . '/config.php';

        $response1 = $this->getHttpMock
        (
            $dir . '/location1.xml',
            $config['response'][0]['isError']
        );

        $response2 = $this->getHttpMock
        (
            $dir . '/conditions.xml',
            $config['response'][1]['isError']
        );

        $response3 = $this->getHttpMock
        (
            $dir . '/location2.xml',
            $config['response'][2]['isError']
        );

        $this->_data = $this->requirePlugin('Http');

        $this->_data->expects($this->any())
            ->method('get')
            ->will($this->onConsecutiveCalls($response1, $response2, $response3));

        switch ($config['unit']) {
        case 'f':
        case 'fahrenheit':
            $method = 'convertFahrenheitToCelsius';
            break;

        case 'c':
        case 'celsius':
            $method = 'convertCelsiusToFahrenheit';
            break;

        default:
            $this->fail(
                'Error with dataset ' . $dataSet . ': $config[\'unit\'] '
                . 'was ' . $config['unit'] . ', but expected the values '
                . '"f", "fahrenheit", "c" or "celsius"'
            );
        }

        $this->_temperature = $this->requirePlugin('Temperature');
        $this->_temperature->expects($this->any())
            ->method($method)
            ->will($this->returnValue($config['temperature']));

        return $config;
    }

    /**
     * Creates a HTTP mock
     *
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    public function getHttpMock($response, $isError = false)
    {
        $http = $this->getMock('Phergie_Plugin_Http_Response');

        $http->expects($this->any())
            ->method('isError')
            ->will($this->returnValue($isError));

        $content = simplexml_load_file($response);
        $http->expects($this->any())
            ->method('getContent')
            ->will($this->returnValue($content));

        return $http;
    }

    /**
     * Tests plugin dependency requirements.
     *
     * @return void
     */
    public function testRequiresCommandPlugin()
    {
        $this->setConfig('weather.partner_id', '1111');
        $this->setConfig('weather.license_key', '1111');

        $this->assertRequiresPlugin(array('Command', 'Http', 'Temperature'));
        $this->plugin->onLoad();
    }

    /**
     *  Tests plugin fails if no weather partner id or license key provided
     *
     *  @return void
     */
    public function testNoConfig()
    {
        try {
            $this->plugin->onLoad();
            $this->fail('Exception should have been thrown');
        } catch( Exception $e) {
            $this->assertInstanceOf('Phergie_Plugin_Exception', $e);
            $this->assertEquals(
                'weather.partner_id and weather.license_key must be specified',
                $e->getMessage()
            );
        }
    }

    /**
     * Tests output of Weather command
     *
     * @dataProvider dataProviderWeatherReports
     *
     * @return void
     */
    public function testGetWeatherReport($test, $location)
    {
        $config = $this->setUpWeatherResponse($test);

        $event = $this->getMockEvent('weathercommand');
        $this->plugin->setEvent($event);

        $this->assertEmitsEvent(
            'privmsg',
            array($this->source,
            $config['weatherReport'])
        );

        $report = $this->plugin->onCommandWeather($location);
    }

    /**
     *  Tests weather data returned
     *
     *  @return void
     */
    public function testGetWeatherData()
    {
        $this->setUpWeatherResponse('atlanta');

        $weatherData = $this->plugin->getWeatherData('atlanta');

        $this->assertEquals($weatherData['temp'], 51);
    }

    public function dataProviderWeatherReports()
    {
        return array(
            array('atlanta',      'atlanta'),
            array('silverSpring', '20904'),
        );
    }
}
