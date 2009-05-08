<?php

/**
 * Php plugin for Phergie.
 * This plugin searches its data source for a description of a Php function.
 */
class Phergie_Plugin_Php extends Phergie_Plugin_Command
{
    /**
     * Datasource to use.
     * @var Php_Source
     */
     private $_source;

    /**
     * Plugin depends on the pdo_sqlite extenions for data storage.
     * @return string
     */
    public function checkDependencies()
    {
        if (!extension_loaded('PDO') || !extension_loaded('pdo_sqlite')) {
            return "PDO Sqlite extension not found.";
        }

        return '';
    }

    /**
     * Instantiate the database
     */
    public function onConnect()
    {
        // Call the parent to register commands
        parent::onConnect();

        // Construct a new datasource
        require_once 'Phergie/Plugin/Php/Source/Local.php';
        $this->_source = new Php_Source_Local;
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
            $msg = "PHP {$function['name']}: {$function['description']}";
        }
        else {
            $msg = "Search for function '{$functionName}' returned no results.";
        }
        
        // Return the result to the source
        $this->doPrivmsg($this->_event->getSource(), $msg);
    }
    
}
