<?php
/**
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
 */
class Twitter_Laconica extends Twitter {

    /**
     * Constructor; sets up configuration.
     * 
     * @param string $user Laconica user name; null for limited read-only access
     * @param string $pass Laconica password; null for limited read-only access
     * @param string $baseUrl Base URL of Laconica install. Defaults to identi.ca
     */
    public function __construct($user=null, $pass=null, $baseUrl = 'http://identi.ca/') {
        $this->baseUrl = $baseUrl;
        parent::__construct($user, $pass);
    }
    
    /**
     * Returns the base API URL
     */
    protected function getUrlApi() {
        return $this->baseUrlFull . 'api/';
    }
    
    /**
     * Output URL: status
     */
    public function getUrlOutputStatus(StdClass $tweet) {
        return $this->baseUrl . 'notice/' . urlencode($tweet->id);
    }
}
