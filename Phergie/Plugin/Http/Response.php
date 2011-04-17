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
 * @copyright 2008-2011 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Plugin_Http
 */

/**
 * Data structure for HTTP response information.
 *
 * @category Phergie
 * @package  Phergie_Plugin_Http
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Plugin_Http
 */
class Phergie_Plugin_Http_Response
{
    /**
     * HTTP response code or 0 if no HTTP response was received
     *
     * @var string
     */
    protected $code;

    /**
     * HTTP response strings
     *
     * @var array
     */
    protected static $codeStrings = array(
        0   => 'No Response',
        100 => 'Continue',
        200 => 'OK',
        201 => 'Created',
        204 => 'No Content',
        206 => 'Partial Content',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        408 => 'Request Timeout',
        410 => 'Gone',
        413 => 'Request Entity Too Large',
        414 => 'Request URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        500 => 'Internal Server Error',
        501 => 'Method Not Implemented',
        503 => 'Service Unavailable',
        506 => 'Variant Also Negotiates'
    );

    /**
     * Description of the HTTP response code or the error message if no HTTP
     * response was received
     *
     * @var string
     */
    protected $message;

    /**
     * Content of the response body, decoded for supported content types
     *
     * @var mixed
     */
    protected $content;

    /**
     * Associative array mapping response header names to their values
     *
     * @var array
     */
    protected $headers;

    /**
     * Associative array containing other metadata about the response
     *
     * @var array
     */
    protected $meta;

    /**
     * Sets the HTTP response code.
     *
     * @param string $code Response code
     *
     * @return Phergie_Plugin_Http_Response Provides a fluent interface
     */
    public function setCode($code)
    {
        $this->code = $code;
        return $this;
    }

    /**
     * Returns the HTTP response code.
     *
     * @return string Response code
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Returns the HTTP response code text.
     *
     * @return string Response code text
     */
    public function getCodeAsString()
    {
        $code = $this->code;

        if (!isset(self::$codeStrings[$code])) {
            return 'Unkown HTTP Status';
        }

        return self::$codeStrings[$code];
    }

    /**
     * Returns whether the response indicates a client- or server-side error.
     *
     * @return bool TRUE if the response indicates an error, FALSE otherwise
     */
    public function isError()
    {
        switch (substr($this->code, 0, 1)) {
        case '0':
        case '4':
        case '5':
            return true;
        default:
            return false;
        }
    }

    /**
     * Sets the HTTP response description.
     *
     * @param string $message Response description
     *
     * @return Phergie_Plugin_Http_Response Provides a fluent interface
     */
    public function setMessage($message)
    {
        $this->message = $message;
        return $this;
    }

    /**
     * Returns the HTTP response description.
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Sets the content of the response body.
     *
     * @param mixed $content Response body content
     *
     * @return Phergie_Plugin_Http_Response Provides a fluent interface
     */
    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Returns the content of the response body.
     *
     * @return mixed Response body content, decoded for supported content
     *         types
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Sets the response headers.
     *
     * @param array $headers Associative array of response headers indexed
     *        by header name
     *
     * @return Phergie_Plugin_Http_Response Provides a fluent interface
     */
    public function setHeaders(array $headers)
    {
        $names = array_map('strtolower', array_keys($headers));
        $values = array_values($headers);
        $this->headers = array_combine($names, $values);
        return $this;
    }

    /**
     * Returns all response headers or the value of a single specified
     * response header.
     *
     * @param string $name Optional name of a single header for which the
     *        associated value should be returned
     *
     * @return array|string Associative array of all header values, a string
     *         containing the value of the header indicated by $name if one
     *         is set, or null if one is not
     */
    public function getHeaders($name = null)
    {
        if ($name) {
            $name = strtolower($name);
            if (empty($this->headers[$name])) {
                return null;
            }
            return $this->headers[$name];
        }
        return $this->headers;
    }

    /**
     * Sets the response metadata.
     *
     * @param array $meta Associative array of response metadata
     *
     * @return Phergie_Plugin_Http_Response Provides a fluent interface
     */
    public function setMeta(array $meta)
    {
        $this->meta = $meta;
        return $this;
    }

    /**
     * Returns all metadata or the value of a single specified metadatum.
     *
     * @param string $name Optional name of a single metadatum for which the
     *        associated value should be returned
     *
     * @return array|string|null Associative array of all metadata values, a
     *         string containing the value of the metadatum indicated by
     *         $name if one is set, or null if one is not
     */
    public function getMeta($name = null)
    {
        if ($name) {
            if (empty($this->meta[$name])) {
                return null;
            }
            return $this->meta[$name];
        }
        return $this->meta;
    }
}
