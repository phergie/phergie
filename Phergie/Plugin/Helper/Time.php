<?php

class Phergie_Plugin_Helper_Time extends Datetime
{
    /**
     * Converts a given integer/timestamp into days, minutes and seconds
     *
     * Borrowed from Phergie 1.x
     *
     * @param int $time The time/integer to calulate the values from
     * @return string
     */
    public function getCountdown()
    {
        $time = time() - $this->format('U');
        $return = array();

        $days = floor($time / 86400);
        if ($days > 0) {
            $return[] = $days . 'd';
            $time %= 86400;
        }

        $hours = floor($time / 3600);
        if ($hours > 0) {
            $return[] = $hours . 'h';
            $time %= 3600;
        }

        $minutes = floor($time / 60);
        if ($minutes > 0) {
            $return[] = $minutes . 'm';
            $time %= 60;
        }

        if ($time > 0 || count($return) <= 0) {
            $return[] = ($time > 0 ? $time : '0') . 's';
        }

        return implode(' ', $return);
    }

}
