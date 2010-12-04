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
 * @package   Phergie_Plugin_Http
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2010 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Plugin_Http
 */

/**
 * Provides an HTTP client for plugins to use in contacting web services or
 * retrieving feeds or web pages.
 *
 * @category Phergie
 * @package  Phergie_Plugin_Http
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Plugin_Http
 * @uses     extension simplexml optional
 * @uses     extension json optional
 */
class Phergie_Plugin_Http extends Phergie_Plugin_Abstract
{
    /**
     * Response to the last executed HTTP request
     *
     * @var Phergie_Plugin_Http_Response
     */
    protected $response;

    /**
     * Mapping of content types to handlers for them
     *
     * @var array
     */
    protected $handlers;

    /**
     * Initializes the handler lookup table.
     *
     * @return void
     */
    public function onLoad()
    {
        $this->handlers = array(
            '(?:text|application)/(?:(?:rss|atom)\+)?xml(?:;.*)?'
                => 'simplexml_load_string',
            '(?:(?:application|text)/(?:x-)?json)|text/javascript.*'
                => 'json_decode',
        );

        if (is_array($this->config['http.handlers'])) {
            $this->handlers = array_merge(
                $this->handlers,
                $this->config['http.handlers']
            );
        }
    }

    /**
     * Sets a handler callback for a content type, which is called when a
     * response of that content type is received to perform any needed
     * transformations on the response body content before storing it in the
     * response object. Note that the calling plugin is responsible for
     * indicating any dependencies related to specified handler callbacks.
     *
     * @param string   $type     PCRE regular expression (without delimiters) that
     *        matches one or more MIME types
     * @param callback $callback Callback to execute when a response of a content
     *        type matched by $type is encountered
     *
     * @return Phergie_Plugin_Http Provides a fluent interface
     */
    public function setHandler($type, $callback)
    {
        if (!is_callable($callback)) {
            throw new Phergie_Plugin_Exception(
                'Invalid callback specified',
                Phergie_Plugin_Exception::ERR_FATAL_ERROR
            );
        }

        $this->handlers[$type] = $callback;

        return $this;
    }

    /**
     * Supporting method that parses the status line of an HTTP response
     * message.
     *
     * @param string $status Status line
     *
     * @return array Associative array containing the HTTP version, response
     *         code, and response description
     */
    protected function parseStatusLine($status)
    {
        $parts = explode(' ', $status, 3);
        $parsed = array(
            'version' => str_replace('HTTP/', '', $parts[0]),
            'code' => $parts[1],
            'message' => rtrim($parts[2])
        );
        return $parsed;
    }

    /**
     * Supporting method that acts as an error handler to intercept HTTP
     * responses resulting in PHP-level errors.
     *
     * @param int    $errno   Level of the error raised
     * @param string $errstr  Error message
     * @param string $errfile Name of the file in which the error was raised
     * @param string $errline Line number on which the error was raised
     *
     * @return bool Always returns TRUE to allow normal execution to
     *         continue once this method terminates
     */
    public function handleError($errno, $errstr, $errfile, $errline)
    {
        if ($httperr = strstr($errstr, 'HTTP/')) {
            $parts = $this->parseStatusLine($httperr);
            $this->response
                ->setCode($parts['code'])
                ->setMessage($parts['message']);
        }

        return true;
    }

    /**
     * Supporting method that executes a request and handles the response.
     *
     * @param string $url     URL to request
     * @param array  $context Associative array of stream context parameters
     *
     * @return Phergie_Plugin_Http_Response Object representing the response
     *         resulting from the request
     */
    public function request($url, array $context)
    {
        $this->response = new Phergie_Plugin_Http_Response;

        $url = (string) $url;
        $context = stream_context_create(array('http' => $context));

        set_error_handler(array($this, 'handleError'), E_WARNING);
        $stream = fopen($url, 'r', false, $context);
        if ($stream) {
            $meta = stream_get_meta_data($stream);
            $status = $this->parseStatusLine($meta['wrapper_data'][0]);
            $code = $status['code'];
            $message = $status['message'];
            $headers = array();
            foreach (array_slice($meta['wrapper_data'], 1) as $header) {
                if (!strpos($header, ':')) {
                    continue;
                }
                list($name, $value) = explode(': ', $header, 2);
                $headers[$name] = $value;
            }
            unset($meta['wrapper_data']);

            $this->response
                ->setCode($code)
                ->setMessage($message)
                ->setHeaders($headers)
                ->setMeta($meta);

            $body = stream_get_contents($stream);
            $type = $this->response->getHeaders('content-type');
            foreach ($this->handlers as $expr => $handler) {
                if (preg_match('#^' . $expr . '$#i', $type)) {
                    $handled = call_user_func($handler, $body);
                    if (!empty($handled)) {
                        $body = $handled;
                    }
                }
            }

            $this->response->setContent($body);
        }
        restore_error_handler();

        return $this->response;
    }

    /**
     * Performs a GET request.
     *
     * @param string $url     URL for the request
     * @param array  $query   Optional associative array of parameters
     *        constituting the URL query string if $url has none
     * @param array  $context Optional associative array of additional stream
     *        context parameters
     *
     * @return Phergie_Plugin_Http_Response Received response data
     */
    public function get($url, array $query = array(), array $context = array())
    {
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        $context['method'] = 'GET';

        return $this->request($url, $context);
    }

    /**
     * Performs a HEAD request.
     *
     * @param string $url     URL for the request
     * @param array  $query   Optional associative array of parameters
     *        constituting the URL query string if $url has none
     * @param array  $context Optional associative array of additional stream
     *        context parameters
     *
     * @return Phergie_Plugin_Http_Response Received response data
     */
    public function head($url, array $query = array(), array $context = array())
    {
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        $context['method'] = 'HEAD';

        return $this->request($url, $context);
    }

    /**
     * Performs a POST request.
     *
     * @param string $url     URL for the request
     * @param array  $query   Optional associative array of parameters
     *        constituting the URL query string if $url has none
     * @param array  $post    Optional associative array of parameters
     *        constituting the POST request body if it is using the
     *        traditional URL-encoded format
     * @param array  $context Optional associative array of additional stream
     *        context parameters
     *
     * @return Phergie_Plugin_Http_Response Received response data
     */
    public function post($url, array $query = array(),
        array $post = array(), array $context = array()
    ) {
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        $context['method'] = 'POST';

        if (!empty($post)
            && (!empty($context['header'])
            xor stripos($context['header'], 'Content-Type'))
        ) {
            if (!empty($context['header'])) {
                $context['header'] .= "\r\n";
            } else {
                $context['header'] = '';
            }
            $context['header'] .=
                'Content-Type: application/x-www-form-urlencoded';
            $context['content'] = http_build_query($post);
        }

        return $this->request($url, $context);
    }
}
