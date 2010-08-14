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
 * Configuration settings:
 * acl.whitelist - mapping of user hostmask patterns (optionally by host) to
 *                 plugins and methods where those plugins and methods will
 *                 only be accessible to those users (i.e. and inaccessible
 *                 to other users)
 * acl.blacklist - mapping of user hostmasks (optionally by host) to plugins
 *                 and methods where where those plugins and methods will be
 *                 inaccessible to those users but accessible to other users
 * acl.ops       - TRUE to automatically give access to whitelisted plugins
 *                 and methods to users with ops for events they initiate in
 *                 channels where they have ops
 *
 * The whitelist and blacklist settings are formatted like so:
 * <code>
 * 'acl.whitelist' => array(
 *     'hostname1' => array(
 *         'pattern1' => array(
 *             'plugins' => array(
 *                 'ShortPluginName'
 *             ),
 *             'methods' => array(
 *                 'methodName'
 *             )
 *         ),
 *     )
 * ),
 * </code>
 *
 * The hostname array dimension is optional; if not used, rules will be
 * applied across all connections. The pattern is a user hostmask pattern
 * where asterisks (*) are used for wildcards. Plugins and methods do not
 * need to be set to empty arrays if they are not used; simply exclude them.
 *
 * @category Phergie
 * @package  Phergie_Plugin_Acl
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Plugin_Acl
 * @uses     Phergie_Plugin_UserInfo pear.phergie.org
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
        $this->plugins->getPlugin('UserInfo');

        if (!$this->getConfig('acl.blacklist')
            && !$this->getConfig('acl.whitelist')
        ) {
            $this->plugins->removePlugin($this);
        }
    }

    /**
     * Applies a set of rules to a plugin handler iterator.
     *
     * @param Phergie_Plugin_Iterator $iterator Iterator to receive rules
     * @param array                   $rules    Associate array containing
     *        either a 'plugins' key pointing to an array containing plugin
     *        short names to filter, a 'methods' key pointing to an array
     *        containing method names to filter, or both
     *
     * @return void
     */
    protected function applyRules(Phergie_Plugin_Iterator $iterator, array $rules)
    {
        if (!empty($rules['plugins'])) {
            $iterator->addPluginFilter($rules['plugins']);
        }
        if (!empty($rules['methods'])) {
            $iterator->addMethodFilter($rules['methods']);
        }
    }

    /**
     * Checks permission settings and short-circuits event processing for
     * blacklisted users.
     *
     * @return void
     */
    public function preEvent()
    {
        // Ignore server responses
        if ($this->event instanceof Phergie_Event_Response) {
            return;
        }

        // Ignore server-initiated events
        if (!$this->event->isFromUser()) {
            return;
        }

        // Get the iterator used to filter plugins when processing events
        $iterator = $this->plugins->getIterator();

        // Get configuration setting values
        $whitelist = $this->getConfig('acl.whitelist', array());
        $blacklist = $this->getConfig('acl.blacklist', array());
        $ops = $this->getConfig('acl.ops', false);

        // Support host-specific lists
        $host = $this->connection->getHost();
        foreach (array('whitelist', 'blacklist') as $var) {
            foreach ($$var as $pattern => $rules) {
                $regex = '/^' . str_replace('*', '.*', $pattern) . '$/i';
                if (preg_match($regex, $host)) {
                    ${$var} = ${$var}[$pattern];
                    break;
                }
            }
        }

        // Get information on the user initiating the current event
        $hostmask = $this->event->getHostmask();
        $isOp = $ops
              && $this->event->isInChannel()
              && $this->plugins->userInfo->isOp(
                $this->event->getNick(),
                $this->event->getSource()
              );

        // Filter whitelisted commands if the user is not on the whitelist
        if (!$isOp) {
            $whitelisted = false;
            foreach ($whitelist as $pattern => $rules) {
                if ($hostmask->matches($pattern)) {
                    $whitelisted = true;
                }
            }
            if (!$whitelisted) {
                foreach ($whitelist as $pattern => $rules) {
                    $this->applyRules($iterator, $rules);
                }
            }
        }

        // Filter blacklisted commands if the user is on the blacklist
        $blacklisted = false;
        foreach ($blacklist as $pattern => $rules) {
            if ($hostmask->matches($pattern)) {
                $this->applyRules($iterator, $rules);
                break;
            }
        }
    }

    /**
     * Clears filters on the plugin handler iterator.
     *
     * @return void
     */
    public function postDispatch()
    {
        $this->plugins->getIterator()->clearFilters();
    }
}
