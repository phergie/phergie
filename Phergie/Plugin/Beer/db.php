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
    if (stripos($name, 'discontinued') !== false) {
        continue;
    }
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
    echo 'openbeerdb.com data set must be downloaded manually from '
        . 'http://www.openbeerdb.com/data', PHP_EOL;
    exit(1);
}

echo 'Decompressing openbeerdb.com data set', PHP_EOL;
$zip = new ZipArchive;
$zip->open(__DIR__ . '/beers.zip');
$zip->extractTo(__DIR__, 'beers/beers.sql');
$file = __DIR__ . '/beers/beers.sql';

// Extract data from data set
echo 'Processing openbeerdb.com data', PHP_EOL;
$fp = fopen($file, 'r');
$db->beginTransaction();
$columns = array();
$start = false;
while ($line = fgets($fp)) {
    if (!$start) {
        if (strpos($line, 'INSERT INTO `beers`') !== false) {
            $start = true;
            $line = rtrim(
                str_replace(
                    array('INSERT INTO `beers` (`', '`) VALUES'),
                    array('', ''),
                    $line
                )
            );
            $columns = explode('`, `', $line);
        }
        continue;
    }
    $line = trim($line, "(),;\r\n");

    $buffer = fopen('php://memory', 'rw');
    fwrite($buffer, $line);
    fseek($buffer, 0);
    $line = array_combine($columns, fgetcsv($buffer, 4096, ',', '\''));
    fclose($buffer);

    $name = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $line['name']);
    $name = preg_replace('/\h*\v+\h*/', '', $name);
    $name = str_replace('\\\'', '\'', $name);
    if (strpos($name, 'discontinued') !== false) {
        continue;
    }

    $link = 'http://www.openbeerdb.com/browse/detail/be_' . $line['id'];
    $insert->execute(array($name, $link));
}
$db->commit();
fclose($fp);

// Clean up
echo 'Cleaning up', PHP_EOL;
unlink($file);
