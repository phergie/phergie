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
    protected $files = array();

    /**
     * Mapping of setting names to their current corresponding values
     *
     * @var array
     */
    protected $settings = array();

    /**
     * Includes a specified PHP configuration file and incorporates its 
     * return value (which should be an associative array) into the current 
     * configuration settings.
     *
     * @param string $file Path to the file to read
     *
     * @return Phergie_Config Provides a fluent interface
     * @throws Phergie_Config_Exception
     */
    public function read($file)
    {
        if (!(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'
            && file_exists($file))
            && !is_executable($file)
        ) {
            throw new Phergie_Config_Exception(
                'Path "' . $file . '" does not reference an executable file',
                Phergie_Config_Exception::ERR_FILE_NOT_EXECUTABLE
            );
        }

        $settings = include $file;
        $this->files[$file] = array_keys($settings); 
        $this->settings += $settings;

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
        foreach ($this->files as $file => &$settings) {
            $values = array();
            foreach ($settings as $setting) {
                $values[$setting] = $this->settings[$setting];
            }
            $source = '<?php' . PHP_EOL . PHP_EOL . 
                'return ' . var_export($value, true) . ';';
            file_put_contents($file, $source); 
        }
    }

    /**
     * Checks to see if a configuration setting is assigned a value.
     *
     * @param string $offset Configuration setting name 
     *
     * @return bool TRUE if the setting has a value, FALSE otherwise
     * @see ArrayAccess::offsetExists()
     */
    public function offsetExists($offset)
    {
        return isset($this->settings[$offset]);
    }

    /**
     * Returns the value of a configuration setting.
     *
     * @param string $offset Configuration setting name
     *
     * @return mixed Configuration setting value or NULL if it is not 
     *         assigned a value
     * @see ArrayAccess::offsetGet()
     */
    public function offsetGet($offset)
    {
        if (isset($this->settings[$offset])) {
            $value = &$this->settings[$offset];
        } else {
            $value = null;
        }

        return $value;
    }

    /**
     * Sets the value of a configuration setting.
     *
     * @param string $offset Configuration setting name
     * @param mixed  $value  New setting value
     *
     * @return void
     * @see ArrayAccess::offsetSet()
     */
    public function offsetSet($offset, $value)
    {
        $this->settings[$offset] = $value;
    }

    /**
     * Removes the value set for a configuration setting.
     *
     * @param string $offset Configuration setting name
     *
     * @return void
     * @see ArrayAccess::offsetUnset()
     */
    public function offsetUnset($offset)
    {
        unset($this->settings[$offset]);

        foreach ($this->files as $file => $settings) {
            $key = array_search($offset, $settings);
            if ($key !== false) {
                unset($this->files[$file][$key]);
            }
        }
    }
}
