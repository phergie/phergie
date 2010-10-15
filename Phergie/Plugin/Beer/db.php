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
    $name = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $beer->textContent);
    $name = preg_replace('/\h*\v+\h*/', '', $name);
    $link = 'http://beerme.com' . $beer->childNodes->item(1)->getAttribute('href');
    $insert->execute(array($name, $link));
}
$db->commit();

// Clean up
echo 'Cleaning up', PHP_EOL;
unlink($file);

// Get and decompress openbeerdb.com data set
$archive = __DIR__ . '/beers.tar.gz';
if (!file_exists($archive)) {
    echo 'openbeerdb.com data set must be downloaded manually from '
        . 'http://groups.google.com/group/openbeerdb/files', PHP_EOL;
    exit(1);
}

echo 'Decompressing openbeerdb.com data set', PHP_EOL;
require_once 'Archive/Tar.php';
$tar = new Archive_Tar($archive, 'gz');
$tar->extractList(array('beers/beers.csv'), __DIR__, 'beers/');
$file = __DIR__ . '/beers.csv';

// Extract data from data set
echo 'Processing openbeerdb.com data', PHP_EOL;
$fp = fopen($file, 'r');
$columns = array_slice(fgetcsv($fp), 0, 12);
$db->beginTransaction();
while ($line = fgetcsv($fp)) {
    if (count($line) < 12) {
        continue;
    }
    $line = array_combine($columns, array_slice($line, 0, 12));
    $name = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $line['name']);
    $name = preg_replace('/\h*\v+\h*/', '', $name);
    $name = str_replace('\\\'', '\'', $name);
    $link = null;
    $insert->execute(array($name, $link));
}
$db->commit();
fclose($fp);

// Clean up
echo 'Cleaning up', PHP_EOL;
unlink($file);
