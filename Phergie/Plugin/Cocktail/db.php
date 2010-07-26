<?php

if (!defined('__DIR__')) {
    define('__DIR__', dirname(__FILE__));
}

// Create database schema
echo 'Creating database', PHP_EOL;
$file = __DIR__ . '/cocktail.db';
if (file_exists($file)) {
    unlink($file);
}
$db = new PDO('sqlite:' . $file);
$db->exec('CREATE TABLE cocktail (name VARCHAR(255), link VARCHAR(255))');
$db->exec('CREATE UNIQUE INDEX cocktail_name ON cocktail (name)');
$insert = $db->prepare('INSERT INTO cocktail (name, link) VALUES (:name, :link)');

// Get raw webtender.com data set
echo 'Downloading webtender.com data set', PHP_EOL;
$start = 1;
do {
    $file = __DIR__ . '/' . $start . '.html';
    if (file_exists($file)) {
        continue;
    }
    copy(
        'http://www.webtender.com/db/browse?level=2&dir=drinks&char=%2A&start=' . $start,
        $file
    );
    if (!isset($limit)) {
        $contents = file_get_contents($file);
        preg_match('/([0-9]+) found/', $contents, $match);
        $limit = $match[1] + (150 - ($match[1] % 150));
    }
    echo 'Got records ', $start, ' - ', min($start + 150, $limit), ' of ', $limit, PHP_EOL;
    $start += 150;
} while ($start < $limit);

// Extract data from data set
$start = 1;
while ($start < $limit) {
    echo 'Processing ', $start, ' - ', min($start + 150, $limit), ' of ', $limit, PHP_EOL;

    $file = __DIR__ . '/' . $start . '.html';
    $contents = file_get_contents($file);
    $contents = tidy_repair_string($contents);
    libxml_use_internal_errors(true);
    $doc = new DOMDocument;
    $doc->loadHTML($contents);
    libxml_clear_errors();
    $xpath = new DOMXPath($doc);

    $cocktails = $xpath->query('//li/a');
    $db->beginTransaction();
    foreach ($cocktails as $cocktail) {
        $name = $cocktail->nodeValue;
        $name = preg_replace('/ The$|^The |\s*\([^)]+\)\s*| #[0-9]+$/', '', $name);
        $name = html_entity_decode($name);
        $link = 'http://www.webtender.com' . $cocktail->getAttribute('href');
        $insert->execute(array($name, $link));
    }
    $db->commit();

    $start += 150;
}

// Clean up
echo 'Cleaning up', PHP_EOL;
$start = 1;
while ($start < $limit) {
    $file = __DIR__ . '/' . $start . '.html';
    unlink($file);
    $start += 150;
}
