<?php

// Add the parent directory of the current file to the include path
set_include_path(get_include_path() . PATH_SEPARATOR . dirname(dirname(__FILE__)));

// Include dependencies
require_once 'Phergie/Bot.php';
require_once 'Phergie/Config.php';
require_once 'Phergie/Connection.php';
require_once 'Phergie/Plugin/Loader.php';

// Load the configuration file 
$config = new Phergie_Config();
$config->read($argc > 1 ? $argv[1] : 'Settings.php');

// Check to ensure a connection list is specified and properly formatted 
if (empty($config['connections']) || !is_array($config['connections'])) {
    trigger_error('The setting \'connections\' is required and must be an array', E_USER_ERROR);
}

$required = array('hostname', 'username', 'realname', 'nick');
foreach ($config['connections'] as $settings) {
    if (!is_array($settings)) {
        trigger_error('Each item in setting \'connections\' must be an array', E_USER_ERROR);
    }
    if (array_intersect(array_keys($settings), $required) != $required) {
        trigger_error('Each item in setting \'connections\' must have the following keys: ' . implode(', ', $required), E_USER_ERROR);
    }
}
unset($required);

// Configure the plugin loader 
$loader = new Phergie_Plugin_Loader(); 
if (isset($config['plugins.autoload'])) {
    $loader->setAutoload($config['plugins.autoload']);
}
$loader->addPath('Plugin', 'Phergie_Plugin_');

// Load plugins 
if (empty($config['plugins'])) {
    trigger_error('The \'plugins\' setting must contain an array of one or more short plugin names', E_USER_ERROR);
}

$remove = array();
foreach ($config['plugins'] as $pluginName) {
    if ($plugin = $loader->addPlugin($pluginName)) {
        $plugin->setConfig($config);
        if($error = $plugin->checkDependencies()) {
            $remove[] = $plugin;
            trigger_error(get_class($plugin) . ': ' . $error, E_USER_WARNING);
        }
     }
 }

// Remove plugins with missing dependencies 
foreach($remove as $plugin) {
    $loader->removePlugin($plugin);
}
unset($remove);

// Configure and start the bot
$bot = new Phergie_Bot();
$bot->setDebug(true);
$bot->getDriver()->setDebug(true);
$bot->setPluginLoader($loader);
foreach ($config['connections'] as $settings) {
    $bot->addConnection(new Phergie_Connection($settings));
}
$bot->run();
