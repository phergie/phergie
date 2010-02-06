#!/usr/bin/env php
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

require 'Autoload.php';
Phergie_Autoload::registerAutoloader();

$bot = new Phergie_Bot;

if ($argc > 0) {
    $config = new Phergie_Config;
    foreach ($argv as $file) {
        $config->read($file);
    }
    $bot->setConfig($config);
}

$bot->run();
