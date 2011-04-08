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
 * @package   Phergie_Plugin_Cache
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2011 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Plugin_Cache
 */

/**
 * Implements a generic cache to be used by other plugins.
 *
 * @category Phergie
 * @package  Phergie_Plugin_Cache
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Plugin_Cache
 */
class Phergie_Plugin_Cache extends Phergie_Plugin_Abstract
{
    /**
     * Key-value data storage for the cache 
     * 
     * @var array 
     */
    protected $cache = array();

    /**
     * Stores a value in the cache. 
     *
     * @param string   $key       Key to associate with the value 
     * @param mixed    $data      Data to be stored
     * @param int|null $ttl       Time to live in seconds or NULL for forever
     * @param bool     $overwrite TRUE to overwrite any existing value 
     *        associated with the specified key
     *
     * @return bool
     */
    public function store($key, $data, $ttl = 3600, $overwrite = true)
    {
        if (!$overwrite && isset($this->cache[$key])) {
            return false;
        }

        if ($ttl) {
            $expires = time()+$ttl;
        } else {
            $expires = null;
        }

        $this->cache[$key] = array('data' => $data, 'expires' => $expires);
        return true;

    }

    /**
     * Fetches a previously stored value. 
     *
     * @param string $key Key associated with the value 
     *
     * @return mixed Stored value or FALSE if no value or an expired value 
     *         is associated with the specified key 
     */
    public function fetch($key)
    {
        if (!isset($this->cache[$key])) {
            return false;
        }

        $item = $this->cache[$key];
        if (!is_null($item['expires']) && $item['expires'] < time()) {
            $this->expire($key);
            return false;
        }

        return $item['data'];
    }

    /**
     * Expires a value that has exceeded its time to live.
     *
     * @param string $key Key associated with the value to expire 
     *
     * @return bool
     */
    protected function expire($key)
    {
        if (!isset($this->cache[$key])) {
            return false;
        }
        unset($this->cache[$key]);
        return true;
    }
}
