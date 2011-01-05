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
 * @package   Phergie
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2010 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie
 */

/**
 * Base class for events.
 *
 * @category Phergie
 * @package  Phergie
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie
 */
abstract class Phergie_Event_Abstract
{
    /**
     * Event type, used for determining the callback to execute in response
     *
     * @var string
     */
    protected $type;

    /**
     * Returns the event type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Sets the event type.
     *
     * @param string $type Event type
     *
     * @return Phergie_Event_Abstract Implements a fluent interface
     */
    public function setType($type)
    {
        $this->type = (string) $type;
        return $this;
    }
}
