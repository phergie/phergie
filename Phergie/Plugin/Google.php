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
 * @package   Phergie_Plugin_Google
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2010 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Plugin_Google
 */

/**
 * Provides commands used to access several services offered by Google 
 * including search, translation, weather, maps, and currency and general 
 * value unit conversion.
 *
 * @category Phergie 
 * @package  Phergie_Plugin_Google
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Plugin_Google
 * @uses     Phergie_Plugin_Command pear.phergie.org
 * @uses     Phergie_Plugin_Http pear.phergie.org
 *
 * @pluginDesc Provide access to some Google services
 */
class Phergie_Plugin_Google extends Phergie_Plugin_Abstract
{

    /**
     * HTTP plugin
     *
     * @var Phergie_Plugin_Http
     */
    protected $http;

    /**
     * Checks for dependencies.
     *
     * @return void
     */
    public function onLoad()
    {
        $plugins = $this->getPluginHandler();
        $plugins->getPlugin('Command');
        $this->http = $plugins->getPlugin('Http');
        $plugins->getPlugin('Help')->register($this);
    }

    /**
     * Returns the first result of a Google search.
     *
     * @param string $query Search term
     *
     * @return void
     * @todo Implement use of URL shortening here
     *
     * @pluginCmd [query] do a search on google
     */
    public function onCommandG($query)
    {
        $url = 'http://ajax.googleapis.com/ajax/services/search/web';
        $params = array(
            'v' => '1.0',
            'q' => $query
        );
        $response = $this->http->get($url, $params);
        $json = $response->getContent()->responseData;
        $event = $this->getEvent();
        $source = $event->getSource();
        $nick = $event->getNick();
        if ($json->cursor->estimatedResultCount > 0) {
            $msg
                = $nick
                . ': [ '
                . $json->results[0]->titleNoFormatting
                . ' ] - '
                . $json->results[0]->url
                . ' - More results: '
                . $json->cursor->moreResultsUrl;
            $this->doPrivmsg($source, $msg);
        } else {
            $msg = $nick . ': No results for this query.';
            $this->doPrivmsg($source, $msg);
        }
    }

    /**
     * Performs a Google Count search for the given term.
     *
     * @param string $query Search term
     *
     * @return void
     *
     * @pluginCmd [query] Do a search on Google and count the results
     */
    public function onCommandGc($query)
    {
        $url = 'http://ajax.googleapis.com/ajax/services/search/web';
        $params = array(
            'v' => '1.0',
            'q' => $query
        );
        $response = $this->http->get($url, $params);
        $json = $response->getContent()->responseData->cursor;
        $count = $json->estimatedResultCount;
        $event = $this->getEvent();
        $source = $event->getSource();
        $nick = $event->getNick();
        if ($count) {
            $msg
                = $nick . ': ' . 
                number_format($count, 0) . 
                ' estimated results for ' . $query;
            $this->doPrivmsg($source, $msg);
        } else {
            $this->doPrivmsg($source, $nick . ': No results for this query.');
        }
    }

    /**
     * Performs a Google Translate search for the given term.
     *
     * @param string $from  Language of the search term
     * @param string $to    Language to which the search term should be 
     *        translated
     * @param string $query Term to translate
     *
     * @return void
     *
     * @pluginCmd [from language] [to language] [text to translate] Do a translation on Google
     */
    public function onCommandGt($from, $to, $query)
    {
        $url = 'http://ajax.googleapis.com/ajax/services/language/translate';
        $params = array(
            'v' => '1.0',
            'q' => $query,
            'langpair' => $from . '|' . $to
        );
        $response = $this->http->get($url, $params);
        $json = $response->getContent();
        $event = $this->getEvent();
        $source = $event->getSource();
        $nick = $event->getNick();
        if (empty($json->responseData->translatedText)) {
            $this->doPrivmsg($source, $nick . ': ' . $json->responseDetails);
        } else {
            $this->doPrivmsg(
                $source, 
                $nick . ': ' . $json->responseData->translatedText
            );
        }
    }

    /**
     * Performs a Google Weather search for the given term.
     * 
     * @param string $location Location to search for
     *
     * @return void
     *
     * @pluginCmd [location] Show the weather for the specified location
     */
    public function onCommandGw($location)
    {
        $url = 'http://www.google.com/ig/api';
        $params = array(
            'weather' => $location,
            'hl' => 'pt-br',
            'oe' => 'UTF-8'
        );
        $response = $this->http->get($url, $params);
        $xml = $response->getContent()->weather;
        $source = $this->getEvent()->getSource();
        if (!isset($xml->problem_cause)) {
            $city = $xml->forecast_information->city->attributes()->data[0];
            $time = $xml->forecast_information->current_date_time->attributes()
                ->data[0];
            $condition = $xml->current_conditions->condition->attributes()->data[0];
            $temp = $xml->current_conditions->temp_c->attributes()->data[0] 
                . '� C';
            $humidity = $xml->current_conditions->humidity->attributes()->data[0];
            $wind = $xml->current_conditions->wind_condition->attributes()->data[0];
            $msg = implode(' - ', array($city, $temp, $condition, $humidity, $wind));
            $this->doPrivmsg($source, $msg);

            foreach ($xml->forecast_conditions as $key => $linha) {
                $day = ucfirst($linha->day_of_week->attributes()->data[0]);
                $min = $linha->low->attributes()->data[0];
                $max = $linha->high->attributes()->data[0];
                $condition = $linha->condition->attributes()->data[0];
                $msg 
                    = 'Forecast: ' . $day . 
                    ' - Min: ' . $min . '� C' . 
                    ' - Max: ' . $max . '� C' . 
                    ' - ' . $condition;
                $this->doPrivmsg($source, $msg);
            }
        } else {
            $this->doPrivmsg($source, $xml->problem_cause->attributes()->data[0]);
        }
    }

    /**
     * Performs a Google Maps search for the given term.
     *
     * @param string $location Location to search for
     *
     * @return void
     *
     * @pluginCmd [location] Get the location from Google Maps to the location specified
     */
    public function onCommandGmap($location)
    {	
        $event = $this->getEvent();
        $source = $event->getSource();
        $nick = $event->getNick();
	
        $location = utf8_encode($location);
        $url = 'http://maps.google.com/maps/geo';
        $params = array(
            'q' => $location,
            'output' => 'json',
            'gl' => 'br',
            'sensor' => 'false',
            'oe' => 'utf8',
            'mrt' => 'all',
            'key' => $this->getConfig('google.key')
        );
        $response = $this->http->get($url, $params); 
        $json =  $response->getContent();
        if (!empty($json)) {
            $qtd = count($json->Placemark);
            if ($qtd > 1) {
                if ($qtd <= 3) {
                    foreach ($json->Placemark as $places) {
                        $xy = $places->Point->coordinates;
                        $address = utf8_decode($places->address);
                        $url = 'http://maps.google.com/maps?sll=' . $xy[1] . ',' 
                            . $xy[0] . '&z=15';
                        $msg = $nick . ' -> ' . $address . ' - ' . $url;
                        $this->doPrivmsg($source, $msg);
                    }
                } else {
                    $msg
                        = $nick . 
                        ', there are a lot of places with that query.' . 
                        ' Try to be more specific!';
                    $this->doPrivmsg($source, $msg);
                }
            } elseif ($qtd == 1) {
                $xy = $json->Placemark[0]->Point->coordinates;
                $address = utf8_decode($json->Placemark[0]->address);
                $url = 'http://maps.google.com/maps?sll=' . $xy[1] . ',' . $xy[0] 
                    . '&z=15';
                $msg = $nick . ' -> ' . $address . ' - ' . $url;
                $this->doPrivmsg($source, $msg);
            } else {
                $this->doPrivmsg($source, $nick . ', I found nothing.');
            }
        } else {
            $this->doPrivmsg($source, $nick . ', we have a problem.');
        }
    }

    /**
     * Perform a Google Convert query to convert a value from one metric to 
     * another.
     *
     * @param string $value Value to convert
     * @param string $from  Source metric
     * @param string $to    Destination metric
     *
     * @return void
     *
     * @pluginCmd [value] [currency from] [currency to] Converts a monetary value from one currency to another
     */
    public function onCommandGconvert($value, $from, $to)
    {
        $url = 'http://www.google.com/finance/converter';
        $params = array(
            'a' => $value,
            'from' => $from,
            'to' => $to
        );
        $response = $this->http->get($url, $params);
        $contents = $response->getContent();
        $event = $this->getEvent();
        $source = $event->getSource();
        $nick = $event->getNick();
        if ($contents) {
            preg_match(
                '#<span class=bld>.*? ' . $to . '</span>#im',
                $contents,
                $matches
            );
            if (!$matches[0]) {
                $this->doPrivmsg($source, $nick . ', I can\'t do that.');
            } else {
                $str = str_replace('<span class=bld>', '', $matches[0]);
                $str = str_replace($to . '</span>', '', $str);
                $text 
                    = number_format($value, 2, ',', '.') . ' ' . $from . 
                    ' => ' . number_format($str, 2, ',', '.') . ' ' . $to;
                $this->doPrivmsg($source, $text);
            }
        } else {
            $this->doPrivmsg($source, $nick . ', we had a problem.');
        }
    }

    /**
     * Performs a Google search to convert a value from one unit to another.
     *
     * @param string $query Query of the form "[quantity] [unit] to [unit2]"
     *
     * @return void
     *
     * @pluginCmd [quantity] [unit] to [unit2] Convert a value from one 
     *            metric to another
     */
    public function onCommandConvert($query)
    {
        $url = 'http://www.google.com/search?q=' . urlencode($query);
        $response = $this->http->get($url);
        $contents = $response->getContent();
        $event = $this->getEvent();
        $source = $event->getSource();
        $nick = $event->getNick();

        if ($response->isError()) {
            $code = $response->getCode();
            $message = $response->getMessage();
            $this->doNotice($nick, 'ERROR: ' . $code . ' ' . $message);
            return;
        }

        $start = strpos($contents, '<h3 class=r>');
        if ($start !== false) {
            $end = strpos($contents, '</b>', $start);
            $text = strip_tags(substr($contents, $start, $end - $start));
            $text = str_replace(
                array(chr(195), chr(151), chr(160)),
                array('x', '', ' '),
                $text
            );
        }

        if (isset($text)) {
            $this->doPrivmsg($source, $nick . ': ' . $text);
        } else {
            $this->doNotice($nick, 'Sorry I couldn\'t find an answer.');
        }
    }
}
