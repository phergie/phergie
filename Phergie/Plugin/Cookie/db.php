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
 * @copyright 2008-2012 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie
 */

// Create database schema
echo 'Creating database', PHP_EOL;
$file = dirname(__FILE__) . '/cookie.db';
if (file_exists($file)) {
    unlink($file);
}
$db = new PDO('sqlite:' . $file);
$db->exec('CREATE TABLE cookies (name VARCHAR(255), link VARCHAR(255))');
$db->exec('CREATE UNIQUE INDEX cookie_name ON cookies (name)');
$insert = $db->prepare('INSERT INTO cookies (name, link) VALUES (:name, :link)');

// Get Cookies list from http://en.wikipedia.org/wiki/List_of_cookies
echo 'Downloading data from Wikipedia', PHP_EOL;
$file = dirname(__FILE__) . '/cookieslist.txt';
if (!file_exists($file)) {
    copy('http://en.wikipedia.org/wiki/List_of_cookies', $file);
}
$contents = file_get_contents($file);

// Extract data from data set
echo 'Processing Wikipedia\'s cookies list', PHP_EOL;
$contents = tidy_repair_string($contents);
libxml_use_internal_errors(true);
$doc = new DOMDocument;
$doc->loadHTML($contents);
libxml_clear_errors();
$xpath = new DOMXPath($doc);

$cookies = $xpath->query('//table[@width="90%"]/tr/td[1]/a');

foreach ($cookies as $cookie) {
    $name = $cookie->textContent;
    $name = str_replace(
        array('(',')',"\n", 'cookies'),
        array('','', ' ', 'cookie'),
        $name
    );
    $name = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $name);
    $name = trim($name);
    $name = rtrim($name, 's');

    $link =  'http://en.wikipedia.org' . $cookie->getAttribute('href');
    $insert->execute(array($name, $link));
    echo 'added [' . $name . '] -> '. $link . PHP_EOL;
}

// Clean up
echo 'Cleaning up', PHP_EOL;
unlink($file);
