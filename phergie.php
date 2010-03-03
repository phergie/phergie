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

/**
 * @see Phergie_Autoload
 */
require 'Phergie/Autoload.php';
Phergie_Autoload::registerAutoloader();

$bot = new Phergie_Bot;

if (!isset($argc)) {
    echo
        'The PHP setting register_argc_argv must be enabled for Phergie ', 
        'configuration files to be specified using command line arguments; ',
        'defaulting to Settings.php in the current working directory',
        PHP_EOL;
} else if ($argc > 0) {
    // Skip the current file for manual installations
    // ex: php phergie.php Settings.php
    if (realpath($argv[0]) == __FILE__) {
        array_shift($argv);
    }

    $config = new Phergie_Config;
    foreach ($argv as $file) {
        $config->read($file);
    }
    $bot->setConfig($config);
}

$bot->run();
