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
 * @package   Phergie_Plugin_Php
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2010 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Plugin_Php
 */

/**
 * URL shortener abstract class
 *
 * @category Phergie 
 * @package  Phergie_Plugin_Url
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Plugin_Url
 * @uses     Phergie_Plugin_Http pear.phergie.org
 */
abstract class Phergie_Plugin_Url_Shorten_Abstract
{
    protected $http;

    /**
     * Constructor
     *
     * @param Phergie_Plugin_Http $http instance of the http plugin
     */
    public function __construct(Phergie_Plugin_Http $http)
    {
        $this->http = $http;
    }

    /**
     * Returns an array of request parameters given a url to shorten. The
     * following keys are valid request parameters:
     *
     *  * 'uri': the URI for the request (required)
     *  * 'query': an array of key-value pairs sent in a GET request
     *  * 'post': an array of key-value pairs sent in a POST request
     *  * 'callback': to be called after the request is finished. Should accept
     *    a Phergie_Plugin_Http_Response object and return either the shortened
     *    url or false if an error has occured.
     *
     * If the 'post' key is present a POST request shall be made; otherwise
     * a GET request will be made. The 'post' key can be an empty array and
     * a post request will still be made.
     *
     * If no callback is provided the contents of the response will be returned.
     *
     * @param string $url the url to shorten
     *
     * @return array the request parameters
     */
    protected abstract function getRequestParams($url);

    /**
     * Shortens a given url.
     *
     * @param string $url the url to shorten
     *
     * @return string the shortened url or false on a failure
     */
    public function shorten($url)
    {
        $defaults = array('get' => array(), 'post' => array(), 'callback' => null);
        $options = array('timeout' => 2);
        $params = $this->getRequestParams($url) + $defaults;

        // Should some kind of notice be thrown? Maybe just if getRequestParams does not return an array?
        if (!is_array($params) || empty($params['uri'])) {
            return $url;
        }

        if (is_array($params['post'])) {
            $response = $this->http->post($params['uri'], $params['get'], $params['post'], $options);
        } else {
            $response = $this->http->get($params['uri'], $params['get'], $options);
        }

        if (is_callable($params['callback'])) {
            return call_user_func($params['callback'], $response);
        }

        return $response->getContent();
    }
}
