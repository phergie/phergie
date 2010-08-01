<?php

if (!defined('__DIR__')) {
    define('__DIR__', dirname(__FILE__));
}

// Create database schema
echo 'Creating database', PHP_EOL;
$file = __DIR__ . '/tea.db';
if (file_exists($file)) {
    unlink($file);
}
$db = new PDO('sqlite:' . $file);
$db->exec('CREATE TABLE tea (name VARCHAR(255), link VARCHAR(255))');
$db->exec('CREATE UNIQUE INDEX tea_name ON tea (name)');
$insert = $db->prepare('INSERT INTO tea (name, link) VALUES (:name, :link)');

// Get raw teacuppa.com data set
echo 'Downloading teacuppa.com data set', PHP_EOL;
$file = __DIR__ . '/tea-list.html';
if (!file_exists($file)) {
    copy('http://www.teacuppa.com/tea-list.asp', $file);
}
$contents = file_get_contents($file);

// Extract data from data set
echo 'Processing teacuppa.com data', PHP_EOL;
$contents = tidy_repair_string($contents);
libxml_use_internal_errors(true);
$doc = new DOMDocument;
$doc->loadHTML($contents);
libxml_clear_errors();
$xpath = new DOMXPath($doc);
$teas = $xpath->query('//p[@class="page_title"]/following-sibling::table//a');
$db->beginTransaction();
foreach ($teas as $tea) {
    $name = preg_replace(
        array('/\s*\v+\s*/', '/\s+tea\s*$/i'),
        array(' ', ''),
        $tea->textContent
    );
    $link = 'http://teacuppa.com/' . $tea->getAttribute('href');
    $insert->execute(array($name, $link));
}
$db->commit();

// Clean up
echo 'Cleaning up', PHP_EOL;
unlink($file);
