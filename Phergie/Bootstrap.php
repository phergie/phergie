<?php

// Set up the autoloader 
include 'Autoload.php';
Phergie_Autoload::registerAutoloader();

// Load the configuration file 
$config = new Phergie_Config;
$config->read($argc > 1 ? $argv[1] : 'Settings.php');

// Configure the bot
$bot = new Phergie_Bot;
$bot->setDebug(true);
$bot->getDriver()->setDebug(true);

// Start the runner
$runner = new Phergie_Runner_Standard;
$runner
    ->setConfig($config)
    ->setBot($bot)
    ->run();
