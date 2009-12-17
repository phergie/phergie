<?php

/**
 * Reads from and writes to PHP configuration files and provides access to 
 * the settings they contain.
 */
class Phergie_Config implements ArrayAccess
{
    /**
     * Mapping of configuration file paths to an array of names of settings 
     * they contain
     *
     * @var array
     */
    protected $_files = array();

    /**
     * Mapping of setting names to their current corresponding values
     *
     * @var array
     */
    protected $_settings = array();

    /**
     * Includes a specified PHP configuration file and incorporates its 
     * return value (which should be an associative array) into the current 
     * configuration settings.
     *
     * @param string $file Path to the file to read
     * @return Phergie_Config Provides a fluent interface
     * @throws Phergie_Config_Exception
     */
    public function read($file)
    {
        if (!(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'
            && file_exists($file))
            && !is_executable($file)) {
            throw new Phergie_Config_Exception(
                'Path "' . $file . '" does not reference an executable file',
                Phergie_Config_Exception::ERR_FILE_NOT_EXECUTABLE
            );
        }

        $settings = require $file;
        $this->_files[$file] = array_keys($settings); 
        $this->_settings += $settings;

        return $this;
    }

    /**
     * Writes the values of the current configuration settings back to their 
     * originating files.
     *
     * @return Phergie_Config Provides a fluent interface
     */
    public function write()
    {
        foreach ($this->_files as $file => &$settings) {
            $values = array();
            foreach ($settings as $setting) {
                $values[$setting] = $this->_settings[$setting];
            }
            $source = '<?php' . PHP_EOL . PHP_EOL . 'return ' . var_export($value, true) . ';';
            file_put_contents($file, $source); 
        }
    }

    /**
     * @see ArrayAccess::offsetExists()
     */
    public function offsetExists($offset)
    {
        return isset($this->_settings[$offset]);
    }

    /**
     * @see ArrayAccess::offsetGet()
     */
    public function offsetGet($offset)
    {
        if (isset($this->_settings[$offset])) {
            $value = &$this->_settings[$offset];
        } else {
            $value = null;
        }

        return $value;
    }

    /**
     * @see ArrayAccess::offsetSet()
     */
    public function offsetSet($offset, $value)
    {
        $this->_setting[$offset] = $value;
    }

    /**
     * @see ArrayAccess::offsetUnset()
     */
    public function offsetUnset($offset)
    {
        unset($this->_settings[$offset]);

        foreach ($this->_files as $file => $settings) {
            $key = array_search($offset, $settings);
            if ($key !== false) {
                unset($this->_files[$file][$key]);
            }
        }
    }
}
