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
 * @copyright 2008-2012 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Plugin_Php
 */

/**
 * Shortens urls via the gsc.io service
 *
 * @category Phergie
 * @package  Phergie_Plugin_Url
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Plugin_Url
 */
class Phergie_Plugin_Url_Shorten_Gscio extends Phergie_Plugin_Url_Shorten_Abstract
{
    /**
     * Gsc URLs will most likely never be longer than 18 characters
     *
     * @var integer
     */
    protected $minimumLength = 19;

    /**
     * Returns an array of request parameters given a url to shorten. The
     * following keys are valid request parameters:
     *
     * @param string $url the url to shorten
     *
     * @return array the request parameters
     */
    protected function getRequestParams($url)
    {
        return array(
            'uri' => 'http://gsc.io/u/?rl=' . rawurlencode($url),
            'callback' => array($this, 'onComplete')
        );
    }

    /**
     * Callback for when the URL has been shortened. Checks for error messages.
     *
     * @param Phergie_Plugin_Http_Response $response the response object
     *
     * @return string|bool the shortened url or false on failure
     */
    protected function onComplete($response)
    {
        if ($response->getCode() == 201) {
            return $response->getContent();
        }

        return false;
    }
}
