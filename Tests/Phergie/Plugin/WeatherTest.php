<?php

class Phergie_Plugin_WeatherTest extends Phergie_Plugin_TestCase
{
	private $data;
	private $temperature;

	private $weatherReport = 'nick: Weather for Atlanta, GA - Temperature: 51F/10.5C, Humidity: 96%, Conditions: Fog, Updated: 3/27/11 12:52 PM EDT [ http://weather.com/weather/today/USGA0028]';

	/**
	 * Mock a HTTP Plugin and prime it with response data.
	 *
	 */	
	public function setUpWeatherResponse()
	{
		$this->setConfig('weather.partner_id','1111');
		$this->setConfig('weather.license_key','1111');
		
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

		$this->data = $this->getMock("Phergie_Plugin_Http",array('get'));

		$this->data->expects($this->any())
			   ->method("get")
			   ->will($this->onConsecutiveCalls($response1,$response2));

		$this->temperature = $this->getMock('Phergie_Plugin_Temperature');
		$this->temperature->expects($this->any())
			   ->method('convertFahrenheitToCelsius')
			   ->will($this->returnValue(10.5));

		$this->getMockPluginHandler()
			 ->expects($this->any())
			 ->method('getPlugin')
			 ->will($this->returnCallback(array($this,'callback')));
	}

	public function callback()
	{
		$args = func_get_args();

		if($args[0] == 'Http')
			return $this->data;
		else
		{
			return $this->temperature;
		}
	}

	public function testOnLoad()
	{
		$this->setConfig('weather.partner_id','1111');
		$this->setConfig('weather.license_key','1111');
		
		$this->assertRequiresPlugin('Command');
		$this->plugin->onLoad();
	}

	public function testNoConfig()
	{
		try
		{
			$this->plugin->onLoad();
			self::fail("Exception should have been thrown");
		}
		catch( Exception $e)
		{
			$this->assertInstanceOf('Phergie_Plugin_Exception',$e);
		}
	}
	
	public function testGetWeatherReport()
	{
		$this->setUpWeatherResponse();
		
		$event = $this->getMockEvent('weathercommand');
		$this->plugin->setEvent($event);

		$this->assertEmitsEvent(
			'privmsg', array($this->source, $this->weatherReport)
		);
		$report = $this->plugin->onCommandWeather('atlanta');
	}

   	public function testGetWeatherData()
	{
		$this->setUpWeatherResponse();
		
		$weatherData = $this->plugin->getWeatherData('atlanta');

		$this->assertEquals($weatherData['temp'],51);
	}
}
