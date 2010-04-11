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
 * @package   Phergie_Plugin_Acl
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2010 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Plugin_Acl
 */

/**
 * Provides an access control system to limit reponses to events based on 
 * the users who originate them.
 *
 * @category Phergie 
 * @package  Phergie_Plugin_Acl
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Plugin_Acl
 */
class Phergie_Plugin_Acl extends Phergie_Plugin_Abstract
{
    /**
     * Checks for permission settings and removes the plugin if none are set.
     *
     * @return void
     */
    public function onLoad()
    {
        if (!$this->getConfig('acl.blacklist')
            && !$this->getConfig('ack.whitelist')) {
            $this->plugins->removePlugin($this);
        }
    }

    /**
     * Checks permission settings and short-circuits event processing for 
     * blacklisted users.
     *
     * @return bool FALSE to short-circuit event processing if the user is 
     *         blacklisted, TRUE otherwise
     */
    public function preEvent()
    {
        // Ignore server responses
        if ($this->event instanceof Phergie_Event_Response) {
            return true;
        }

        // Ignore server-initiated events
        if (!$this->event->isFromUser()) {
            return true;
        }

        // Determine whether a whitelist or blacklist is being used
        $list = $this->getConfig('acl.whitelist');
        $matches = true;
        if (!$list) {
            $list = $this->getConfig('acl.blacklist');
            $matches = false;
        }

        // Support host-specific lists 
        $host = $this->connection->getHost();
        if (isset($list[$host])) {
            $list = $list[$host];
        }

        // Short-circuit event processing if appropriate 
        $hostmask = $this->event->getHostmask();
        foreach ($list as $pattern) {
            if ($hostmask->matches($pattern)) {
                return $matches;
            }
        }

        // Allow event processing if appropriate 
        return !$matches;
    }
}
