<?php

/**
 * Monitors incoming messages for instances of URLs and responds with messages
 * containing relevant information about detected URLs.
 *
 * Has an utility method accessible through $this->getPlugin('Url')->getTitle('http://foo..')
 */
class Phergie_Plugin_Url extends Phergie_Plugin_Abstract
{
    /**
     * Links output format
     *
     * Can use the variables %nick%, %title% and %link% in it to display page titles
     * and links
     *
     * @var string
     */
    protected $_baseFormat = '%nick%: %message%';
    protected $_messageFormat = '[ %link% ] %title%';

    /**
     * Merged link output
     *
     * If true, then multiple posted links will be merged into one line
     *
     * @var bool
     */
    protected $_mergeLinks = true;

    /**
     * Max length of the fetched URL title
     *
     * @var int
     */
    protected $_titleLength = 40;

    /**
     * Url cache to prevent spamming, especially with multiple bots on the same channel
     */
    protected $_urlCache = array();
    protected $_shortCache = array();

    /**
     * The time in seconds to store the cached entries
     * Setting it to 0 or below disables the cache expiration
     */
    protected $_expire = 1800;

    /**
     * The number of entries to keep in the cache at one time per channel
     * Setting it to 0 or below disables the cache limit
     */
    protected $_limit = 10;

    /**
     * This setting determines if URL will use a fallback when trying to open
     * a https stream when OpenSSL isn't available, instead it will try opening
     * a http stream instead.
     */
    protected $_sslFallback = true;

    /**
     * Set to true by the custom error handler if an HTTP error code has been received
     *
     * @var boolean
     */
    protected $_errorStatus = false;
    protected $_errorMessage = null;

    /**
     * Whether or not to display error messages as the title if a link posted
     * encounters an error.
     *
     * @var boolean
     */
    protected $_showErrors = true;

    /**
     * Whether or not to detect schemeless urls (i.e. "example.com")
     *
     * @var boolean
     */
    protected $_detectSchemeless = false;

    /**
     * List of HTTP errors to return when the requested URL returns an HTTP error
     *
     * @var array
     */
    protected $httpErrors = array(
        100 => '100 Continue',
        200 => '200 OK',
        201 => '201 Created',
        204 => '204 No Content',
        206 => '206 Partial Content',
        300 => '300 Multiple Choices',
        301 => '301 Moved Permanently',
        302 => '302 Found',
        303 => '303 See Other',
        304 => '304 Not Modified',
        307 => '307 Temporary Redirect',
        400 => '400 Bad Request',
        401 => '401 Unauthorized',
        403 => '403 Forbidden',
        404 => '404 Not Found',
        405 => '405 Method Not Allowed',
        406 => '406 Not Acceptable',
        408 => '408 Request Timeout',
        410 => '410 Gone',
        413 => '413 Request Entity Too Large',
        414 => '414 Request URI Too Long',
        415 => '415 Unsupported Media Type',
        416 => '416 Requested Range Not Satisfiable',
        417 => '417 Expectation Failed',
        500 => '500 Internal Server Error',
        501 => '501 Method Not Implemented',
        503 => '503 Service Unavailable',
        506 => '506 Variant Also Negotiates'
    );

    /**
     * An array containing a list of TLDs used for non-scheme matches
     *
     * @var array
     */
    protected $_tldList = array();

    /**
     * Shortener object
     */
    protected $_shortener;

    /**
     * Array of renderers
     */
    protected $_renderers = array();

    /**
     * Initializes settings, checks dependencies
     *
     * @return void
     */
    public function onConnect()
    {
        // make the shortener configurable
        $shortener = isset($this->_config['url.shortener']) ?
            $this->_config['url.shortener'] :
            'Trim';
        $shortener = "Phergie_Plugin_Url_Shorten_{$shortener}";
        $this->_shortener = new $shortener;

        if (!$this->_shortener instanceof Phergie_Plugin_Url_Shorten_Abstract) {
            $this->_fail("Declared shortener class {$shortener} is not of proper ancestry");
        }

        // Get a list of valid TLDs
        if (!is_array($this->_tldList) || count($this->_tldList) <= 6) {
            /* Omitted for port
            if ($this->pluginLoaded('Tld')) {
                $this->_tldList = Phergie_Plugin_Tld::getTlds();
                if (is_array($this->_tldList)) {
                    $this->_tldList = array_keys($this->_tldList);
                }
            }
            */
            if (!is_array($this->_tldList) || count($this->_tldList) <= 0) {
                $this->_tldList = array('ac', 'ad', 'ae', 'aero', 'af', 'ag', 'ai', 'al', 'am', 'an', 'ao', 'aq', 'ar', 'arpa', 'as', 'asia', 'at', 'au', 'aw', 'ax', 'az', 'ba', 'bb', 'bd', 'be', 'bf', 'bg', 'bh', 'bi', 'biz', 'bj', 'bl', 'bm', 'bn', 'bo', 'br', 'bs', 'bt', 'bv', 'bw', 'by', 'bz', 'ca', 'cat', 'cc', 'cd', 'cf', 'cg', 'ch', 'ci', 'ck', 'cl', 'cm', 'cn', 'co', 'com', 'coop', 'cr', 'cu', 'cv', 'cx', 'cy', 'cz', 'de', 'dj', 'dk', 'dm', 'do', 'dz', 'ec', 'edu', 'ee', 'eg', 'eh', 'er', 'es', 'et', 'eu', 'fi', 'fj', 'fk', 'fm', 'fo', 'fr', 'ga', 'gb', 'gd', 'ge', 'gf', 'gg', 'gh', 'gi', 'gl', 'gm', 'gn', 'gov', 'gp', 'gq', 'gr', 'gs', 'gt', 'gu', 'gw', 'gy', 'hk', 'hm', 'hn', 'hr', 'ht', 'hu', 'id', 'ie', 'il', 'im', 'in', 'info', 'int', 'io', 'iq', 'ir', 'is', 'it', 'je', 'jm', 'jo', 'jobs', 'jp', 'ke', 'kg', 'kh', 'ki', 'km', 'kn', 'kp', 'kr', 'kw', 'ky', 'kz', 'la', 'lb', 'lc', 'li', 'lk', 'lr', 'ls', 'lt', 'lu', 'lv', 'ly', 'ma', 'mc', 'md', 'me', 'mf', 'mg', 'mh', 'mil', 'mk', 'ml', 'mm', 'mn', 'mo', 'mobi', 'mp', 'mq', 'mr', 'ms', 'mt', 'mu', 'museum', 'mv', 'mw', 'mx', 'my', 'mz', 'na', 'name', 'nc', 'ne', 'net', 'nf', 'ng', 'ni', 'nl', 'no', 'np', 'nr', 'nu', 'nz', 'om', 'org', 'pa', 'pe', 'pf', 'pg', 'ph', 'pk', 'pl', 'pm', 'pn', 'pr', 'pro', 'ps', 'pt', 'pw', 'py', 'qa', 're', 'ro', 'rs', 'ru', 'rw', 'sa', 'sb', 'sc', 'sd', 'se', 'sg', 'sh', 'si', 'sj', 'sk', 'sl', 'sm', 'sn', 'so', 'sr', 'st', 'su', 'sv', 'sy', 'sz', 'tc', 'td', 'tel', 'tf', 'tg', 'th', 'tj', 'tk', 'tl', 'tm', 'tn', 'to', 'tp', 'tr', 'travel', 'tt', 'tv', 'tw', 'tz', 'ua', 'ug', 'uk', 'um', 'us', 'uy', 'uz', 'va', 'vc', 've', 'vg', 'vi', 'vn', 'vu', 'wf', 'ws', 'ye', 'yt', 'yu', 'za', 'zm', 'zw');
            }
            rsort($this->_tldList);
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
            ) as $config => $local) {
            if (isset($this->_config["url.{$config}"])) {
                $this->$local = $this->_config["uri.{$config}"];
            }
        }
    }

    /**
     * Checks an incoming message for the presence of a URL and, if one is
     * found, responds with its title if it is an HTML document and the
     * shortened equivalent of its original URL if it meets length requirements.
     *
     * @return void
     */
    public function onPrivmsg()
    {
        $source = $this->getEvent()->getSource();
        $user = $this->getEvent()->getNick();

        // URL Match
        if (preg_match_all('#'.($this->_detectSchemeless ? '' : 'https?://').'(?:([0-9]{1,3}(?:\.[0-9]{1,3}){3})(?![^/]) |
                            ('.($this->_detectSchemeless ? '(?<!http:/|https:/)[@/\\\]' : '').')?(?:(?:[a-z0-9_-]+\.?)+\.[a-z0-9]{1,6}))[^\s]*#xis',
                            $this->getEvent()->getArgument(1), $matches, PREG_SET_ORDER)) {

            // Update the settings on the fly to take into account any ini changes while the bot is running
			// TODO: removed in 2.x ; might want to add it back?

            $responses = array();
            foreach($matches as $m) {
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

                // allow out-of-class renderers to handle this URL
                foreach ($this->_renderers as $renderer) {
                    if ($renderer->renderUrl($parsed) === true) {
                        // renderers should return true if they've fully
                        // rendered the passed URL (they're responsible
                        // for their own output)
                        $this->debug('Handled by renderer: ' . get_class($renderer));
                        continue 2;
                    }
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
	                if (is_array($this->_tldList) && !in_array(strtolower($parsed['tld']), $this->_tldList)) {
	                    $this->debug('Invalid Url: ' . $parsed['tld'] . ' is not a supported TLD. (' . $url . ')');
	                    continue;
	                }
                }

                // Check to see if the URL is to a secured site or not and handle it accordingly
                if ($parsed['scheme'] == 'https' && !extension_loaded('openssl')) {
                    if (!$this->_sslFallback) {
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
                $url = $this->glueURL($parsed);
                unset($parsed);

                // Convert url
                $shortenedUrl = $this->_shortener->shorten($url);

                // Prevent spamfest
                if ($this->checkUrlCache($url, $shortenedUrl)) {
                    $this->debug('Invalid Url: URL is in the cache. (' . $url . ')');
                    continue;
                }

                $title = self::getTitle($url);
                if (!empty($title)) {
                    $responses[] = str_replace(array(
                        '%title%',
                        '%link%',
                        '%nick%'
                    ), array(
                        $title,
                        $shortenedUrl,
                        $user
                    ), $this->_messageFormat);
                }

                // Update cache
                $this->updateUrlCache($url, $shortenedUrl);
                unset($title, $shortenedUrl, $title);
            }
            /**
             * Check to see if there were any URL responses, format them and handle if they
             * get merged into one message or not
             */
            if (count($responses) > 0) {
                if ($this->_mergeLinks) {
                    $this->doPrivmsg($source, str_replace(array(
                        '%message%',
                        '%nick%'
                    ), array(
                        implode('; ', $responses),
                        $user
                    ), $this->_baseFormat));
                } else {
                    foreach($responses as $response) {
                        $this->doPrivmsg($source, str_replace(array(
                            '%message%',
                            '%nick%'
                        ), array(
                            $response,
                            $user
                        ), $this->_baseFormat));
                    }
                }
            }
        }
    }

    /**
     * Checks a given URL (+shortened) against the cache to verify if they were
     * previously posted on the channel.
     *
     * @param string $url The URL to check against
     * @param string $shortenedUrl The shortened URL to check against
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
            'url' => isset($this->_urlCache[$source][$url]) ? $this->_urlCache[$source][$url] : null,
            'shortened' => isset($this->_shortCache[$source][$shortenedUrl]) ? $this->_shortCache[$source][$shortenedUrl] : null
        );

        $expire = $this->_expire;
        $this->debug("Cache expire: {$expire}");
        /**
         * If cache expiration is enabled, check to see if the given url has expired in the cache
         * If expire is disabled, simply check to see if the url is listed
         */
        if (($expire > 0 && (($cache['url'] + $expire) > time() || ($cache['shortened'] + $expire) > time())) ||
            ($expire <= 0 && (isset($cache['url']) || isset($cache['shortened'])))) {
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
     * @param string $url The URL to add to the cache
     * @param string $shortenedUrl The shortened to add to the cache
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
        $this->_urlCache[$source][$url] = $time;
        if ($this->_limit > 0 && count($this->_urlCache[$source]) > $this->_limit) {
            asort($this->_urlCache[$source], SORT_NUMERIC);
            array_shift($this->_urlCache[$source]);
        }

        // Handle the shortened cache and remove old entries that surpass the limit if enabled
        $this->_shortCache[$source][$shortenedUrl] = $time;
        if ($this->_limit > 0 && count($this->_shortCache[$source]) > $this->_limit) {
            asort($this->_shortCache[$source], SORT_NUMERIC);
            array_shift($this->_shortCache[$source]);
        }
        unset($url, $shortenedUrl, $time);
    }

    /**
     * Transliterates a UTF-8 string into corresponding ASCII characters and
     * truncates and appends an ellipsis to the string if it exceeds a given
     * length.
     *
     * @param string $str String to decode
     * @param int $trim Maximum string length, optional
     * @return string
     */
    protected function decode($str, $trim = null)
    {
        $out = $this->decodeTranslit($str);
        if ($trim > 0) {
            $out = substr($out, 0, $trim) . (strlen($out) > $trim ? '...' : '');
        }
        return $out;
    }

    /**
     * Custom error handler meant to handle 404 errors and such
     */
    public function onPhpError($errno, $errstr, $errfile, $errline)
    {
        if ($errno === E_WARNING) {
            // Check to see if there was HTTP warning while connecting to the site
            if (preg_match('{HTTP/1\.[01] ([0-9]{3})}i', $errstr, $m)) {
                $this->_errorStatus = $m[1];
                $this->_errorMessage = (isset($this->httpErrors[$m[1]]) ? $this->httpErrors[$m[1]] : $m[1]);
                $this->debug('PHP Warning:  ' . $errstr . 'in ' . $errfile . ' on line ' . $errline);
                return true;
            // Safely ignore these SSL warnings so they don't appear in the log
            } else if (stripos($errstr, 'SSL: fatal protocol error in') !== false ||
                       stripos($errstr, 'failed to open stream') !== false ||
                       stripos($errstr, 'HTTP request failed') !== false ||
                       stripos($errstr, 'SSL: An existing connection was forcibly closed by the remote host') !== false ||
                       stripos($errstr, 'Failed to enable crypto in') !== false ||
                       stripos($errstr, 'SSL: An established connection was aborted by the software in your host machine') !== false ||
                       stripos($errstr, 'SSL operation failed with code') !== false ||
                       stripos($errstr, 'unable to connect to') !== false) {
                $this->_errorStatus = true;
                $this->debug('PHP Warning:  ' . $errstr . 'in ' . $errfile . ' on line ' . $errline);
                return true;
            }
        }
        return false;
    }

    /**
     * Takes a url, parses and cleans the URL without of all the junk
     * and then return the hex checksum of the url.
     */
    protected function getUrlChecksum($url)
    {
        $checksum = strtolower(urldecode($this->glueUrl($url, true)));
        $checksum = preg_replace('#\s#', '', $this->decodeTranslit($checksum));
        return dechex(crc32($checksum));
    }

    /*
    * Parses a given URI and procceses the output to remove redundant
    * or missing values.
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

    /*
    * Parses a given URI and then glues it back together in the proper format.
    * If base is set, then it chops off the scheme, user and pass and fragment
    * information to return a more unique base URI.
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
            if (!empty($parsed['port']) &&
                (($parsed['scheme'] == 'http' && $parsed['port'] == 80) ||
                ($parsed['scheme'] == 'https' && $parsed['port'] == 443))) {
                unset($parsed['port']);
            }
            $uri .= (!empty($parsed['port']) ? ':' . $parsed['port'] : '');
            if(!empty($parsed['path']) && (!$base || $base && $parsed['path'] != '/'))
            {
                $uri .= (substr($parsed['path'], 0, 1) == '/') ? $parsed['path'] : ('/' . $parsed['path']);
            }
            $uri .= (!empty($parsed['query']) ? '?' . $parsed['query'] : '');
            if (!$base) {
                $uri .= (!empty($parsed['fragment']) ? '#' . $parsed['fragment'] : '');
            }
        }
        return $uri;
    }

    /*
    * Checks the given string to see if its a valid IP4 address
    */
    protected function checkValidIP($ip) {
        return long2ip(ip2long($ip)) === $ip;
    }

    /**
 	 * Returns the title of the given page
 	 *
 	 * @param string $url url to the page
 	 * @return string title
 	 */
    public function getTitle($url)
    {
		$opts = array(
			'http' => array(
				'timeout' => 3.5,
				'method' => 'GET',
				'user_agent' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.12) Gecko/20080201 Firefox/2.0.0.12'
			)
		);
		$context = stream_context_create($opts);

		if ($page = fopen($url, 'r', false, $context)) {
			stream_set_timeout($page, 3.5);
			$data = stream_get_meta_data($page);
			foreach($data['wrapper_data'] as $header) {
				if (preg_match('/^Content-Type: ([^;]+)/', $header, $match) &&
					!preg_match('#^(text/x?html|application/xhtml+xml)$#', $match[1])) {
					$title = $match[1];
				}
			}
			if (!isset($title)) {
				$content = '';
				$tstamp = time() + 5;

				while ($chunk = fread($page, 64)) {
					$data = stream_get_meta_data($page);
					if ($data['timed_out']) {
						$this->debug('Url Timed Out: ' . $url);
						$this->_errorStatus = true;
						break;
					}
					$content .= $chunk;
					// Check for timeout
					if (time() > $tstamp) break;
					// Try to read title
					if (preg_match('#<title[^>]*>(.*)#is', $content, $m)) {
						// Start another loop to grab some more data in order to be sure we have the complete title
						$content = $m[1];
						$loop = 2;
						while (($chunk = fread($page, 64)) && $loop-- && !strstr($content, '<')) {
							$content .= $chunk;
							// Check for timeout
							if (time() > $tstamp) break;
						}
						preg_match('#^([^<]*)#is', $content, $m);
						$title = preg_replace('#\s+#', ' ', $m[1]);
						$title = trim($this->decode($title, $this->_titleLength));
						break;
					}
					// Title won't appear beyond that point so stop parsing
					if (preg_match('#</head>|<body#i', $content)) {
						break;
					}
				}
			}
			fclose($page);
		} else if (!$this->_errorStatus) {
			$this->debug('Couldn\t Open Url: ' . $url);
		}

		if (empty($title)) {
			if ($this->_errorStatus) {
				if (!$this->_showErrors || empty($this->_errorMessage)) {
					return;
				}
				$title = $this->_errorMessage;
				$this->_errorStatus = false;
				$this->_errorMessage = null;
			} else {
				$title = 'No Title';
			}
		}

		return $title;
    }

    protected function debug($msg)
    {
        echo "(DEBUG:Url) $msg\n";
    }

    protected function decodeTranslit($str)
    {
        // placeholder/porting helper
        return $str;
    }

    public function registerRenderer($obj) {
        $this->_renderers[] = $obj;
        array_unique($this->_renderers);
    }

}
