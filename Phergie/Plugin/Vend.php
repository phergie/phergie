<?php
/**
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
 * @package   Phergie_Plugin_Vend
 * @author    John Congdon <john@johncongdon.com>
 * @copyright 2012 John Congdon (http://www.johncongdon.com)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Plugin_Vend
 */

/**
 * Parses and Vends via itvends.com
 *
 * @category Phergie
 * @package  Phergie_Plugin_Vend
 * @author   John Congdon <john@johncongdon.com>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Plugin_Vend
 * @uses     Phergie_Plugin_Command pear.phergie.org
 */
class Phergie_Plugin_Vend extends Phergie_Plugin_Abstract
{
    /**
     * Number of reminders to show in public.
     */
    protected $vend_url = 'http://itvends.com/vend.php?format=json&count=1';

    /**
     * Check for dependencies.
     *
     * @return void
     */
    public function onLoad()
    {
        $plugins = $this->getPluginHandler();
        $plugins->getPlugin('Command');
    }

    /**
     * Handle vend requests
     *
     * @param string $recipient recipient of the message
     *
     * @return void
     * @see doVend()
     */
    public function onCommandVend($recipient)
    {
        $this->doVend($recipient);
    }

    public function getItem()
    {
        $json = file_get_contents($this->vend_url);
        $array = json_decode($json);
        return $array[0];
    }

    protected function doVend($recipient)
    {
        $source = $this->getEvent()->getSource();
        $nick = $this->getEvent()->getNick();

        $item = $this->getItem();
        if (strtolower($recipient) == 'me') //simple hack for self sending
        {
            $recipient = $nick;
        }
        $this->doAction($source, 'sends ' . $recipient . ' ' . $item);
    }
}
