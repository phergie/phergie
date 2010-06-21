<?php
class Phergie_Plugin_Cron extends Phergie_Plugin_Abstract
{
    /**
     * Array of all callbacks registered with delay and arguments.
     * 
     * @var array
     *
     **/

    protected $callbacks;

    /**
     *  Registers a callback. Callback param may be:
     * 1. array( 'Class' , 'Function' )
     * 2. array( $instance , 'Function' )
     *  The $repeat bool indicates if the callback  will be repeated. FALSE is 
     * default value which means that after the first call of method the callback
     * will be unregistered and not executed anymore. When a callback is repeated
     * does not mean that the callback will be executed every $delay sec but,
     * that the callback will be scheduled to execute, from the last time plus
     * delay.
     * 
     * @param array $callback  Callback to be registered
     * @param int   $delay     Delay in seconds
     * @param array $arguments Arguments for the callback
     * @param bool  $repeat    Repeat the callback 
     *
     * @return void
     **/

    public function registerCallback( $callback, $delay, $arguments=array(), $repeat=false )
    {
        if (!is_callable($callback)) {
            echo 'DEBUG(Cron): Invalid callback specified - ',
                var_export($callback, true),PHP_EOL;
            return;
        } else {
            $classname = ( is_string($callback[0]) )?
                $callback[0] : get_class($callback[0]);
            $this->callbacks[] = array(
                'call' => $callback,
                'delay'=>$delay,
                'args'=>$arguments,
                'registered'=>time(),
                'repeat'=>$repeat
            );
            echo 'DEBUG(Cron): Callback registered '.$classname.' '.$callback[1].
                " scheduled for ".date('H:i:s', time()+$delay)."\n";
            unset($classname);
        }
        
    }

    /**
     * Check if is time for a callback and if so executes and unset it.
     * 
     * @return void 
     **/
    
    public function onTick() 
    {
        $time = time();
        foreach ( $this->callbacks as $key=>$call ) {
            $stime = $call['registered'] + $call['delay'];
            if ( $stime  < $time ) {
                if ( empty($call['args']) ) {
                    call_user_func($call['call']);
                } else { 
                    call_user_func_array($call['call'], $call['args']);
                }
                $debughead = "DEBUG(Cron) Callback: ".
                    $call['call'][0]."::".$call['call'][1];
                echo $debughead." executed at ".date('H:i:s', $time).
                    " scheduled for ".date('H:i:s', $stime)."\n";
                if ( $call['repeat'] ) {
                    echo $debughead." next execution at ".
                        date('H:i:s', $time+$call['delay']).".\n";
                    $this->callbacks[$key]['registered'] = $time;
                } else {
                    echo $debughead." removed from callback list.\n";
                    unset($this->callbacks[$key]);
                }
                unset($debughead);
            }
        }
    }
}
