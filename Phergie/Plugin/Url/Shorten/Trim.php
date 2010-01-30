<?php

/**
 * URL shortener: tr.im
 */
class Phergie_Plugin_Url_Shorten_Trim extends Phergie_Plugin_Url_Shorten_Abstract
{
    public function shorten($url)
    {
        return file_get_contents('http://api.tr.im/v1/trim_simple?url=' . rawurlencode($url));
    }
}
