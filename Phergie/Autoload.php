<?php

/**
 * Autoloader for Phergie classes.
 */
class Phergie_Autoload
{
    /**
     * Constructor to add the base Phergie path to the include_path.
     *
     * @return void
     */
    public function __construct()
    {
        $path = dirname(__FILE__);
        $includePath = get_include_path();
        $includePathList = explode(PATH_SEPARATOR, $includePath); 
        if (!in_array($path, $includePathList)) {
            set_include_path($includePath . PATH_SEPARATOR . $path);
        }
    }

    /**
     * Autoload callback for loading class files.
     *
     * @param string $class Class to load
     * @return void
     */
    public function load($class)
    {
        if (substr($class, 0, 8) == 'Phergie_') {
            $class = substr($class, 8);
        }
        include str_replace('_', DIRECTORY_SEPARATOR, $class) . '.php';
    }

    /**
     * Registers an instance of this class as an autoloader.
     *
     * @return void
     */
    public static function registerAutoloader()
    {
        spl_autoload_register(array(new self, 'load'));
    }
}
