<?php

/**
 * Returns information on PHP functions as requested. 
 */
class Phergie_Plugin_Php extends Phergie_Plugin_Abstract
{
    /**
     * Data source to use
     *
     * @var Phergie_Plugin_Php_Source
     */
    protected $_source;

    /**
     * Check for dependencies.
     *
     * @return void
     */
    public function onLoad()
    {
        if (!extension_loaded('PDO') || !extension_loaded('pdo_sqlite')) {
            $this->fail('PDO and pdo_sqlite extensions must be installed');
        }
    }

    /**
     * Instantiate the database
     */
    public function onConnect()
    {
        // Call the parent to register commands
        parent::onConnect();

        // Construct a new data source
        require_once 'Phergie/Plugin/Php/Source/Local.php';
        $this->_source = new Phergie_Plugin_Php_Source_Local;
    }

    /**
     * Search the database for the function
     * 
     * @param string $functionName
     */
    public function onDoPhp($functionName)
    {
        // Search for the function
        if($function = $this->_source->findFunction($functionName)) {
            $msg = 'PHP ' . $function['name'] . ': ' . $function['description'];
        }
        else {
            $msg = 'Search for function ' . $functionName . ' returned no results.';
        }
        
        // Return the result to the source
        $this->doPrivmsg($this->getEvent()->getSource(), $msg);
    }
}
