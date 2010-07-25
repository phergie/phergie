<?php

if (!defined('__DIR__')) {
    define('__DIR__', dirname(__FILE__));
}

// Create database schema
echo 'Creating database', PHP_EOL;
$file = __DIR__ . '/beer.db';
if (file_exists($file)) {
    unlink($file);
}
$db = new PDO('sqlite:' . $file);
$db->exec('CREATE TABLE beer (name VARCHAR(255), link VARCHAR(255))');
$db->exec('CREATE UNIQUE INDEX beer_name ON beer (name)');
$insert = $db->prepare('INSERT INTO beer (name, link) VALUES (:name, :link)');

// Get raw beerme.com data set
echo 'Downloading beerme.com data set', PHP_EOL;
$file = __DIR__ . '/beerlist.txt';
if (!file_exists($file)) {
    copy('http://beerme.com/beerlist.php', $file);
}
$contents = file_get_contents($file);

// Extract data from data set
echo 'Processing beerme.com data', PHP_EOL;
$contents = tidy_repair_string($contents);
libxml_use_internal_errors(true);
$doc = new DOMDocument;
$doc->loadHTML($contents);
libxml_clear_errors();
$xpath = new DOMXPath($doc);
$beers = $xpath->query('//table[@class="beerlist"]/tr/td[1]');
$db->beginTransaction();
foreach ($beers as $beer) {
    $name = $beer->textContent;
    $link = 'http://beerme.com' . $beer->childNodes->item(1)->getAttribute('href');
    $insert->execute(array($name, $link));
}
$db->commit();

// Clean up
echo 'Cleaning up', PHP_EOL;
unlink($file);

// Get and decompress openbeerdb.com data set
$archive = __DIR__ . '/beers.zip';
if (!file_exists($archive)) {
    echo 'Downloading openbeerdb.com data set', PHP_EOL;
    copy('http://openbeerdb.googlecode.com/files/beers.zip', $archive);
}

echo 'Decompressing openbeerdb.com data set', PHP_EOL;
$zip = new ZipArchive;
$zip->open($archive);
$zip->extractTo(__DIR__, 'beers/beers.csv');
$zip->close();
$file = __DIR__ . '/beers/beers.csv';

// Extract data from data set
echo 'Processing openbeerdb.com data', PHP_EOL;
$fp = fopen($file, 'r');
$columns = fgetcsv($fp, 0, '|');
$db->beginTransaction();
while ($line = fgetcsv($fp, 0, '|')) {
    $line = array_combine($columns, $line);
    $name = $line['name'];
    $link = null;
    $insert->execute(array($name, $link));
}
$db->commit();
fclose($fp);

// Clean up
echo 'Cleaning up', PHP_EOL;
unlink($file);
unlink($archive);
rmdir(__DIR__ . '/beers');
