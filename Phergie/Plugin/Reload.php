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
 * @package   Phergie_Plugin_Reload
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2010 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Plugin_Reload
 */

/**
 * Facilitates reloading of individual plugins for development purposes.
 * Note that, because existing class definitions cannot be removed from
 * memory, increased memory usage is an expected result of using this plugin.
 *
 * @category Phergie
 * @package  Phergie_Plugin_Reload
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Plugin_Reload
 * @uses     Phergie_Plugin_Command pear.phergie.org
 */
class Phergie_Plugin_Reload extends Phergie_Plugin_Abstract
{
    /**
     * Checks for dependencies.
     *
     * @return void
     */
    public function onLoad()
    {
        $this->getPluginHandler()->getPlugin('Command');
    }

    /**
     * Reloads a specified plugin.
     *
     * @param string $plugin Short name of the plugin to reload
     *
     * @return void
     */
    public function onCommandReload($plugin)
    {
        $plugin = ucfirst($plugin);

        if (!$this->plugins->hasPlugin($plugin)) {
            echo 'DEBUG(Reload): ' . ucfirst($plugin) . ' is not loaded yet, loading', PHP_EOL;
            $this->plugins->getPlugin($plugin);
            $this->plugins->command->populateMethodCache();
            return;
        }

        try {
            $info = $this->plugins->getPluginInfo($plugin);
        } catch (Phergie_Plugin_Exception $e) {
            $source = $this->event->getSource();
            $nick = $this->event->getNick();
            $this->doNotice($source, $nick . ': ' . $e->getMessage());
            return;
        }

        $class = $info['class'];
        $contents = file_get_contents($info['file']);
        $newClass = $class . '_' . sha1($contents);

        if (class_exists($newClass, false)) {
            echo 'DEBUG(Reload): Class ', $class, ' has not changed since last reload', PHP_EOL;
            return;
        }

        $contents = preg_replace(
            array('/^<\?(?:php)?/', '/class\s+' . $class . '/i'),
            array('', 'class ' . $newClass),
            $contents
        );
        eval($contents);

        $instance = new $newClass;
        $instance->setName($plugin);
        $instance->setEvent($this->event);
        $this->plugins
            ->removePlugin($plugin)
            ->addPlugin($instance);

        $this->plugins->command->populateMethodCache();

        echo 'DEBUG(Reload): Reloaded ', $class, ' to ', $newClass, PHP_EOL;
    }
}
