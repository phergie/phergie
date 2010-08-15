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
 * @package   Phergie_Plugin_Url
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2010 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Plugin_Url
 */

/**
 * Monitors incoming messages for instances of URLs and responds with messages
 * containing relevant information about detected URLs.
 *
 * Has an utility method accessible via
 * $this->getPlugin('Url')->getTitle('http://foo..').
 *
 * @category Phergie
 * @package  Phergie_Plugin_Url
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Plugin_Url
 * @uses     Phergie_Plugin_Encoding pear.phergie.org
 * @uses     Phergie_Plugin_Http pear.phergie.org
 * @uses     Phergie_Plugin_Tld pear.phergie.org
 */
class Phergie_Plugin_Url extends Phergie_Plugin_Abstract
{
    /**
     * Links output format
     *
     * Can use the variables %nick%, %title% and %link% in it to display
     * page titles and links
     *
     * @var string
     */
    protected $baseFormat = '%message%';
    protected $messageFormat = '[ %link% ] %title%';

    /**
     * Flag indicating whether a single response should be sent for a single
     * message containing multiple links
     *
     * @var bool
     */
    protected $mergeLinks = true;

    /**
     * Max length of the fetched URL title
     *
     * @var int
     */
    protected $titleLength = 40;

    /**
     * Url cache to prevent spamming, especially with multiple bots on the
     * same channel
     *
     * @var array
     */
    protected $urlCache = array();
    protected $shortCache = array();

    /**
     * Time in seconds to store the cached entries
     *
     * Setting it to 0 or below disables the cache expiration
     *
     * @var int
     */
    protected $expire = 1800;

    /**
     * Number of entries to keep in the cache at one time per channel
     *
     * Setting it to 0 or below disables the cache limit
     *
     * @var int
     */
    protected $limit = 10;

    /**
     * Flag that determines if the plugin will fall back to using an HTTP
     * stream when a URL using SSL is detected and OpenSSL support isn't
     * available in the PHP installation in use
     *
     * @var bool
     */
    protected $sslFallback = true;

    /**
     * Flag that is set to true by the custom error handler if an HTTP error
     * code has been received
     *
     * @var boolean
     */
    protected $errorStatus = false;
    protected $errorMessage = null;

    /**
     * Flag indicating whether or not to display error messages as the title
     * if a link posted encounters an error
     *
     * @var boolean
     */
    protected $showErrors = true;

    /**
     * Flag indicating whether to detect schemeless URLS (i.e. "example.com")
     *
     * @var boolean
     */
    protected $detectSchemeless = false;

    /**
     * Shortener object
     */
    protected $shortener;

    /**
     * Array of renderers
     */
    protected $renderers = array();

    /**
     * Checks for dependencies.
     *
     * @return void
     */
    public function onLoad()
    {
        $plugins = $this->plugins;
        $plugins->getPlugin('Encoding');
        $plugins->getPlugin('Http');
        $plugins->getPlugin('Tld');

        // make the shortener configurable
        $shortener = $this->getConfig('url.shortener', 'Trim');
        $shortener = "Phergie_Plugin_Url_Shorten_{$shortener}";
        $this->shortener = new $shortener($this->plugins->getPlugin('Http'));

        if (!$this->shortener instanceof Phergie_Plugin_Url_Shorten_Abstract) {
            $this->fail("Declared shortener class {$shortener} is not of proper ancestry");
        }

        // load config (a bit ugly, but focusing on porting):
        foreach (
            array(
                'detect_schemeless' => 'detectSchemeless',
                'base_format' => 'baseFormat',
                'message_format' => 'messageFormat',
                'merge_links' => 'mergeLinks',
                'title_length' => 'titleLength',
                'show_errors' => 'showErrors',
                'expire' => 'expire',
            ) as $config => $local) {
            if (isset($this->config["url.{$config}"])) {
                $this->$local = $this->config["uri.{$config}"];
            }
        }
    }

    /**
     * Checks an incoming message for the presence of a URL and, if one is
     * found, responds with its title if it is an HTML document and the
     * shortened equivalent of its original URL if it meets length requirements.
     *
     * @todo Update this to pull configuration settings from $this->config
     *       rather than caching them as class properties
     * @return void
     */
    public function onPrivmsg()
    {
        $this->handleMsg();
    }

    /**
     * Checks an incoming message for the presence of a URL and, if one is
     * found, responds with its title if it is an HTML document and the
     * shortened equivalent of its original URL if it meets length requirements.
     *
     * @todo Update this to pull configuration settings from $this->config
     *       rather than caching them as class properties
     * @return void
     */
    public function onAction()
    {
        $this->handleMsg();
    }

    /**
     * Handles message events and responds with url titles.
     *
     * @return void
     */
    protected function handleMsg()
    {
        $source = $this->getEvent()->getSource();
        $user = $this->getEvent()->getNick();

        $responses = array();
        $urls = $this->findUrls($this->getEvent()->getArgument(1));

        foreach ($urls as $parsed) {
            $url = $parsed['glued'];

            // allow out-of-class renderers to handle this URL
            foreach ($this->renderers as $renderer) {
                if ($renderer->renderUrl($parsed) === true) {
                    // renderers should return true if they've fully
                    // rendered the passed URL (they're responsible
                    // for their own output)
                    $this->debug('Handled by renderer: ' . get_class($renderer));
                    continue 2;
                }
            }

            // Convert url
            $shortenedUrl = $this->shortener->shorten($url);
            if (!$shortenedUrl) {
                $this->debug('Invalid Url: Unable to shorten. (' . $url . ')');
                $shortenedUrl = $url;
            }

            // Prevent spamfest
            if ($this->checkUrlCache($url, $shortenedUrl)) {
                $this->debug('Invalid Url: URL is in the cache. (' . $url . ')');
                continue;
            }

            $title = $this->getTitle($url);
            if (!empty($title)) {
                $responses[] = str_replace(
                    array(
                        '%title%',
                        '%link%',
                        '%nick%'
                    ), array(
                        $title,
                        $shortenedUrl,
                        $user
                    ), $this->messageFormat
                );
            }

            // Update cache
            $this->updateUrlCache($url, $shortenedUrl);
            unset($title, $shortenedUrl, $title);
        }

        // Check to see if there were any URL responses, format them and handle if they
        // get merged into one message or not
        if (count($responses) > 0) {
            if ($this->mergeLinks) {
                $message = str_replace(
                    array(
                        '%message%',
                        '%nick%'
                    ), array(
                        implode('; ', $responses),
                        $user
                    ), $this->baseFormat
                );
                $this->doPrivmsg($source, $message);
            } else {
                foreach ($responses as $response) {
                    $message = str_replace(
                        array(
                            '%message%',
                            '%nick%'
                        ), array(
                            implode('; ', $responses),
                            $user
                        ), $this->baseFormat
                    );
                    $this->doPrivmsg($source, $message);
                }
            }
        }
    }

    /**
     * Detect URLs in a given string.
     *
     * @param string $message the string to detect urls in
     *
     * @return array the array of urls found
     */
    public function findUrls($message)
    {
        $pattern = '#'.($this->detectSchemeless ? '' : 'https?://').'(?:([0-9]{1,3}(?:\.[0-9]{1,3}){3})(?![^/]) | ('
            .($this->detectSchemeless ? '(?<!http:/|https:/)[@/\\\]' : '').')?(?:(?:[a-z0-9_-]+\.?)+\.[a-z0-9]{1,6}))[^\s]*#xis';
        $urls = array();

        // URL Match
        if (preg_match_all($pattern, $message, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $url = trim(rtrim($m[0], ', ].?!;'));

                // Check to see if the URL was from an email address, is a directory, etc
                if (!empty($m[2])) {
                    $this->debug('Invalid Url: URL is either an email or a directory path. (' . $url . ')');
                    continue;
                }

                // Parse the given URL
                if (!$parsed = $this->parseUrl($url)) {
                    $this->debug('Invalid Url: Could not parse the URL. (' . $url . ')');
                    continue;
                }

                // Check to see if the given IP/Host is valid
                if (!empty($m[1]) and !$this->checkValidIP($m[1])) {
                    $this->debug('Invalid Url: ' . $m[1] . ' is not a valid IP address. (' . $url . ')');
                    continue;
                }

                // Process TLD if it's not an IP
                if (empty($m[1])) {
                    // Get the TLD from the host
                    $pos = strrpos($parsed['host'], '.');
                    $parsed['tld'] = ($pos !== false ? substr($parsed['host'], ($pos+1)) : '');

                    // Check to see if the URL has a valid TLD
                    if ($this->plugins->tld->getTld($parsed['tld']) === false) {
                        $this->debug('Invalid Url: ' . $parsed['tld'] . ' is not a supported TLD. (' . $url . ')');
                        continue;
                    }
                }

                // Check to see if the URL is to a secured site or not and handle it accordingly
                if ($parsed['scheme'] == 'https' && !extension_loaded('openssl')) {
                    if (!$this->sslFallback) {
                        $this->debug('Invalid Url: HTTPS is an invalid scheme, OpenSSL isn\'t available. (' . $url . ')');
                        continue;
                    } else {
                        $parsed['scheme'] = 'http';
                    }
                }

                if (!in_array($parsed['scheme'], array('http', 'https'))) {
                    $this->debug('Invalid Url: ' . $parsed['scheme'] . ' is not a supported scheme. (' . $url . ')');
                    continue;
                }

                $urls[] = $parsed + array('glued' => $this->glueURL($parsed));
            }
        }

        return $urls;
    }

    /**
     * Checks a given URL (+shortened) against the cache to verify if they were
     * previously posted on the channel.
     *
     * @param string $url          The URL to check against
     * @param string $shortenedUrl The shortened URL to check against
     *
     * @return bool
     */
    protected function checkUrlCache($url, $shortenedUrl)
    {
        $source = $this->getEvent()->getSource();

        /**
         * Transform the URL (+shortened) into a HEX CRC32 checksum to prevent potential problems
         * and minimize the size of the cache for less cache bloat.
         */
        $url = $this->getUrlChecksum($url);
        $shortenedUrl = $this->getUrlChecksum($shortenedUrl);

        $cache = array(
            'url' => isset($this->urlCache[$source][$url]) ? $this->urlCache[$source][$url] : null,
            'shortened' => isset($this->shortCache[$source][$shortenedUrl]) ? $this->shortCache[$source][$shortenedUrl] : null
        );

        $expire = $this->expire;
        $this->debug("Cache expire: {$expire}");
        /**
         * If cache expiration is enabled, check to see if the given url has expired in the cache
         * If expire is disabled, simply check to see if the url is listed
         */
        if (($expire > 0 && (($cache['url'] + $expire) > time() || ($cache['shortened'] + $expire) > time()))
            || ($expire <= 0 && (isset($cache['url']) || isset($cache['shortened'])))
        ) {
            unset($cache, $url, $shortenedUrl, $expire);
            return true;
        }
        unset($cache, $url, $shortenedUrl, $expire);
        return false;
    }

    /**
     * Updates the cache and adds the given URL (+shortened) to the cache. It
     * also handles cleaning the cache of old entries as well.
     *
     * @param string $url          The URL to add to the cache
     * @param string $shortenedUrl The shortened to add to the cache
     *
     * @return bool
     */
    protected function updateUrlCache($url, $shortenedUrl)
    {
        $source = $this->getEvent()->getSource();

        /**
         * Transform the URL (+shortened) into a HEX CRC32 checksum to prevent potential problems
         * and minimize the size of the cache for less cache bloat.
         */
        $url = $this->getUrlChecksum($url);
        $shortenedUrl = $this->getUrlChecksum($shortenedUrl);
        $time = time();

        // Handle the URL cache and remove old entries that surpass the limit if enabled
        $this->urlCache[$source][$url] = $time;
        if ($this->limit > 0 && count($this->urlCache[$source]) > $this->limit) {
            asort($this->urlCache[$source], SORT_NUMERIC);
            array_shift($this->urlCache[$source]);
        }

        // Handle the shortened cache and remove old entries that surpass the limit if enabled
        $this->shortCache[$source][$shortenedUrl] = $time;
        if ($this->limit > 0 && count($this->shortCache[$source]) > $this->limit) {
            asort($this->shortCache[$source], SORT_NUMERIC);
            array_shift($this->shortCache[$source]);
        }
        unset($url, $shortenedUrl, $time);
    }

    /**
     * Transliterates a UTF-8 string into corresponding ASCII characters and
     * truncates and appends an ellipsis to the string if it exceeds a given
     * length.
     *
     * @param string $str  String to decode
     * @param int    $trim Maximum string length, optional
     *
     * @return string
     */
    protected function decode($str, $trim = null)
    {
        $out = $this->plugins->encoding->transliterate($str);
        if ($trim > 0) {
            $out = substr($out, 0, $trim) . (strlen($out) > $trim ? '...' : '');
        }
        return $out;
    }

    /**
     * Takes a url, parses and cleans the URL without of all the junk
     * and then return the hex checksum of the url.
     *
     * @param string $url url to checksum
     *
     * @return string the hex checksum of the cleaned url
     */
    protected function getUrlChecksum($url)
    {
        $checksum = strtolower(urldecode($this->glueUrl($url, true)));
        $checksum = preg_replace('#\s#', '', $this->plugins->encoding->transliterate($checksum));
        return dechex(crc32($checksum));
    }

    /**
     * Parses a given URI and procceses the output to remove redundant
     * or missing values.
     *
     * @param string $url the url to parse
     *
     * @return array the url components
     */
    protected function parseUrl($url)
    {
        if (is_array($url)) return $url;

        $url = trim(ltrim($url, ' /@\\'));
        if (!preg_match('&^(?:([a-z][-+.a-z0-9]*):)&xis', $url, $matches)) {
            $url = 'http://' . $url;
        }
        $parsed = parse_url($url);

        if (!isset($parsed['scheme'])) {
            $parsed['scheme'] = 'http';
        }
        $parsed['scheme'] = strtolower($parsed['scheme']);

        if (isset($parsed['path']) && !isset($parsed['host'])) {
            $host = $parsed['path'];
            $path = '';
            if (strpos($parsed['path'], '/') !== false) {
                list($host, $path) = array_pad(explode('/', $parsed['path'], 2), 2, null);
            }
            $parsed['host'] = $host;
            $parsed['path'] = $path;
        }

        return $parsed;
    }

    /**
     * Parses a given URI and then glues it back together in the proper format.
     * If base is set, then it chops off the scheme, user and pass and fragment
     * information to return a more unique base URI.
     *
     * @param string $uri  uri to rebuild
     * @param string $base set to true to only return the base components
     *
     * @return string the rebuilt uri
     */
    protected function glueUrl($uri, $base = false)
    {
        $parsed = $uri;
        if (!is_array($parsed)) {
            $parsed = $this->parseUrl($parsed);
        }

        if (is_array($parsed)) {
            $uri = '';
            if (!$base) {
                $uri .= (!empty($parsed['scheme']) ? $parsed['scheme'] . ':' .
                        ((strtolower($parsed['scheme']) == 'mailto') ? '' : '//') : '');
                $uri .= (!empty($parsed['user']) ? $parsed['user'] .
                        (!empty($parsed['pass']) ? ':' . $parsed['pass'] : '') . '@' : '');
            }
            if ($base && !empty($parsed['host'])) {
                $parsed['host'] = trim($parsed['host']);
                if (substr($parsed['host'], 0, 4) == 'www.') {
                    $parsed['host'] = substr($parsed['host'], 4);
                }
            }
            $uri .= (!empty($parsed['host']) ? $parsed['host'] : '');
            if (!empty($parsed['port'])
                && (($parsed['scheme'] == 'http' && $parsed['port'] == 80)
                || ($parsed['scheme'] == 'https' && $parsed['port'] == 443))
            ) {
                unset($parsed['port']);
            }
            $uri .= (!empty($parsed['port']) ? ':' . $parsed['port'] : '');
            if (!empty($parsed['path']) && (!$base || $base && $parsed['path'] != '/')) {
                $uri .= (substr($parsed['path'], 0, 1) == '/') ? $parsed['path'] : ('/' . $parsed['path']);
            }
            $uri .= (!empty($parsed['query']) ? '?' . $parsed['query'] : '');
            if (!$base) {
                $uri .= (!empty($parsed['fragment']) ? '#' . $parsed['fragment'] : '');
            }
        }
        return $uri;
    }

    /**
     * Checks the given string to see if its a valid IP4 address
     *
     * @param string $ip the ip to validate
     *
     * @return bool
     */
    protected function checkValidIP($ip)
    {
        return long2ip(ip2long($ip)) === $ip;
    }

    /**
     * Returns the title of the given page
     *
     * @param string $url url to the page
     *
     * @return string title
     */
    public function getTitle($url)
    {
        $http = $this->plugins->getPlugin('Http');
        $options = array(
            'timeout' => 3.5,
            'user_agent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.12) Gecko/20080201 Firefox/2.0.0.12'
        );

        $response = $http->head($url, array(), $options);

        if ( $response->getCode() == 405 ) { // [Head] request method not allowed

            $response = $http->get($url, array(), $options);
        }


        $header = $response->getHeaders('Content-Type');

        if (!preg_match('#^(text/x?html|application/xhtml+xml)(?:;.*)?$#', $header)) {
            $title = $header;
        } else {
            $response = $http->get($url, array(), $options);
            $content = $response->getContent();
            if (preg_match('#<title[^>]*>(.*?)</title>#is', $content, $match)) {
                $title = preg_replace('/[\s\v]+/', ' ', trim($match[1]));
            }
        }
        $encoding = $this->plugins->getPlugin('Encoding');
        $title = $encoding->decodeEntities($title);

        if (empty($title)) {
            if ($response->isError()) {
                $title = $response->getCodeAsString();
            } else {
                $title = 'No Title';
            }
        }

        return $title;
    }

    /**
     * Output a debug message
     *
     * @param string $msg the message to output
     *
     * @return void
     */
    protected function debug($msg)
    {
        echo "(DEBUG:Url) $msg\n";
    }

    /**
     * Add a renderer to the stack
     *
     * @param object $obj the renderer to add
     *
     * @return void
     */
    public function registerRenderer($obj)
    {
        $this->renderers[spl_object_hash($obj)] = $obj;
    }
}
