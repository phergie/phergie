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
     * Registers a callback. Callback param may be:
     * 1. array( 'Class' , 'Function' )
     * 2. array( $instance , 'Function' )
     * 
     * @param array $callback  Callback to be registered
     * @param int   $delay     Delay in seconds
     * @param array $arguments Arguments for the callback
     *
     * @return void
     **/

    public function registerCallback( $callback, $delay, $arguments = array() )
    {
        $time = time();
        if (!is_callable($callback)) {
            echo 'DEBUG(Cron): Invalid callback specified - ',
                var_export($callback, true),PHP_EOL;
            return;
        } else {
            $classname = ( is_string($callback[0]) )?
                $callback[0] : get_class($callback[0]);
            $this->callbacks[] = array(
                'call' => $callback,
                'time'=>$time+$delay,
                'args'=>$arguments
            );
            echo 'DEBUG(Cron): Callback registered '.$classname.' '.$callback[1].
                " scheduled for ".date('H:i:s', $time+$delay)."\n";
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
            if ( $call['time']  < $time ) {
                if ( empty($call['args']) ) {
                    call_user_func($call['call']);
                } else { 
                    call_user_func_array($call['call'], $call['args']);
                }
                echo "DEBUG(Cron) Callback: ".
                    $call['call'][0]."::".$call['call'][1].
                    " at ".date('H:i:s', $time)." scheduled for".
                    date('H:i:s', $call['time'])."\n";
                unset($this->callbacks[$key]);
            }
        }
    }
}
