<?php
/**
 * Phergie
 *
 * Sean's Simple Twitter Library - Laconica extension
 *
 * Copyright 2008, Sean Coates
 * Usage of the works is permitted provided that this instrument is retained
 * with the works, so that any entity that uses the works is notified of this
 * instrument.
 * DISCLAIMER: THE WORKS ARE WITHOUT WARRANTY.
 * ( Fair License - http://www.opensource.org/licenses/fair.php )
 * Short license: do whatever you like with this.
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
class Twitter_Laconica extends Twitter
{

    /**
     * Constructor; sets up configuration.
     * 
     * @param string $user    Laconica user name; null for limited read-only access
     * @param string $pass    Laconica password; null for limited read-only access
     * @param string $baseUrl Base URL of Laconica install. Defaults to identi.ca
     */
    public function __construct(
        $user=null, $pass=null, $baseUrl = 'http://identi.ca/'
    ) {
        $this->baseUrl = $baseUrl;
        parent::__construct($user, $pass);
    }
    
    /**
     * Returns the base API URL
     *
     * @return void
     */
    protected function getUrlApi()
    {
        return $this->baseUrlFull . 'api/';
    }
    
    /**
     * Output URL: status
     *
     * @param StdClass $tweet TODO: Desc
     *
     * @return void
     */
    public function getUrlOutputStatus(StdClass $tweet)
    {
        return $this->baseUrl . 'notice/' . urlencode($tweet->id);
    }
}
