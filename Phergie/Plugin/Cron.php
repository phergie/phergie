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
 * @package   Phergie_Plugin_Cron
 * @author    Phergie Development Team <team@phergie.org>
 * @copyright 2008-2010 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie_Plugin_Cron
 */

/**
 * Allows callbacks to be registered for asynchronous execution.
 *
 * @category Phergie
 * @package  Phergie_Plugin_Cron
 * @author   Phergie Development Team <team@phergie.org>
 * @license  http://phergie.org/license New BSD License
 * @link     http://pear.phergie.org/package/Phergie_Plugin_Cron
 */
class Phergie_Plugin_Cron extends Phergie_Plugin_Abstract
{
    /**
     * Array of all registered callbacks with delays and arguments
     *
     * @var array
     */
    protected $callbacks = array();

    /**
     * Returns a human-readable representation of a callback for debugging
     * purposes.
     *
     * @param callback $callback Callback to analyze
     *
     * @return string|boolean String representation of the callback or FALSE
     *         if the specified value is not a valid callback
     */
    protected function getCallbackString($callback)
    {
        if (!is_callable($callback)) {
            return false;
        }

        if (is_array($callback)) {
            $class = is_string($callback[0]) ?
                $callback[0] : get_class($callback[0]);
            $method = $class . '::' . $callback[1];
            return $method;
        }

        return $callback;
    }

    /**
     * Registers a callback for execution sometime after a given delay
     * relative to now.
     *
     * @param callback $callback  Callback to be registered
     * @param int      $delay     Delay in seconds from now when the callback
     *        will be executed
     * @param array    $arguments Arguments to pass to the callback when
     *        it's executed
     * @param bool     $repeat    TRUE to automatically re-register the
     *        callback for the same delay after it's executed, FALSE
     *        otherwise
     *
     * @return void
     */
    public function registerCallback($callback, $delay,
        array $arguments = array(), $repeat = false)
    {
        $callbackString = $this->getCallbackString($callback);
        if ($callbackString === false) {
            echo 'DEBUG(Cron): Invalid callback specified - ',
                var_export($callback, true), PHP_EOL;
            return;
        }

        $registered = time();
        $scheduled = $registered + $delay;

        $this->callbacks[] = array(
            'callback'   => $callback,
            'delay'      => $delay,
            'arguments'  => $arguments,
            'registered' => $registered,
            'scheduled'  => $scheduled,
            'repeat'     => $repeat,
        );

        echo 'DEBUG(Cron): Callback ', $callbackString,
            ' scheduled for ', date('H:i:s', $scheduled), PHP_EOL;
    }

    /**
     * Handles callback execution.
     *
     * @return void
     */
    public function onTick()
    {
        $time = time();
        foreach ($this->callbacks as $key => &$callback) {
            $callbackString = $this->getCallbackString($callback);

            $scheduled = $callback['scheduled'];
            if ($time < $scheduled) {
                continue;
            }

            if (empty($callback['arguments'])) {
                call_user_func($callback['callback']);
            } else {
                call_user_func_array(
                    $callback['callback'],
                    $callback['arguments']
                );
            }

            echo 'DEBUG(Cron): Callback ', $callbackString,
                ' scheduled for ', date('H:i:s', $scheduled), ',',
                ' executed at ', date('H:i:s', $time), PHP_EOL;

            if ($callback['repeat']) {
                $callback['scheduled'] = $time + $callback['delay'];
                echo 'DEBUG(Cron): Callback ', $callbackString,
                    ' scheduled for ', date('H:i:s', $callback['scheduled']),
                    PHP_EOL;
            } else {
                echo 'DEBUG(Cron): Callback ', $callbackString,
                    ' removed from callback list', PHP_EOL;
                unset($this->callbacks[$key]);
            }
        }
    }
}
