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
    private $_files = array();

    /**
     * Mapping of setting names to their current corresponding values
     *
     * @var array
     */
    private $_settings = array();

    /**
     * Includes a specified PHP configuration file and incorporates its 
     * return value (which should be an associative array) into the current 
     * configuration settings.
     *
     * @param string $file Path to the file to read
     * @return Phergie_Config Provides a fluent interface
     */
    public function read($file)
    {
        if (!(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'
            && file_exists($file))
            && !is_executable($file)) {
            trigger_error($file . ' does not reference an executable file', E_USER_ERROR);
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
        foreach ($this->_files as $file => $settings) {
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
        if (!empty($this->_settings[$offset])) {
            return $this->_settings[$offset];
        }
        return null;
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
