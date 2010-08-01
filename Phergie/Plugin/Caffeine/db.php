<?php

if (!defined('__DIR__')) {
    define('__DIR__', dirname(__FILE__));
}

// Create database schema
echo 'Creating database', PHP_EOL;
$file = __DIR__ . '/caffeine.db';
if (file_exists($file)) {
    unlink($file);
}
$db = new PDO('sqlite:' . $file);
$db->exec('CREATE TABLE caffeine (name VARCHAR(255), link VARCHAR(255))');
$db->exec('CREATE UNIQUE INDEX caffeine_name ON caffeine (name)');
$insert = $db->prepare('INSERT INTO caffeine (name, link) VALUES (:name, :link)');

// Get raw energyfiend.com data set
echo 'Downloading energyfiend.com data set', PHP_EOL;
$file = __DIR__ . '/the-caffeine-database.html';
if (!file_exists($file)) {
    copy('http://www.energyfiend.com/the-caffeine-database', $file);
}
$contents = file_get_contents($file);

// Extract data from data set
echo 'Processing energyfiend.com data', PHP_EOL;
$contents = tidy_repair_string($contents);
libxml_use_internal_errors(true);
$doc = new DOMDocument;
$doc->loadHTML($contents);
libxml_clear_errors();
$xpath = new DOMXPath($doc);
$caffeine = $xpath->query('//table[@id="caffeinedb"]//tr/td[1]');
$db->beginTransaction();
foreach ($caffeine as $drink) {
    $name = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $drink->textContent);
    $name = preg_replace('/\s*\v+\s*/', ' ', $name);
    if ($drink->firstChild->nodeName == 'a') {
        $link = 'http://energyfiend.com'
              . $drink->firstChild->getAttribute('href');
    } else {
        $link = null;
    }
    $insert->execute(array($name, $link));
}
$db->commit();

// Clean up
echo 'Cleaning up', PHP_EOL;
unlink($file);
