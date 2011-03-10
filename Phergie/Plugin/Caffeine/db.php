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

// Create database schema
echo 'Creating database', PHP_EOL;
$file = dirname(__FILE__) . '/caffeine.db';
if (file_exists($file)) {
    unlink($file);
}
$db = new PDO('sqlite:' . $file);
$db->exec('CREATE TABLE caffeine (name VARCHAR(255), link VARCHAR(255))');
$db->exec('CREATE UNIQUE INDEX caffeine_name ON caffeine (name)');
$insert = $db->prepare('INSERT INTO caffeine (name, link) VALUES (:name, :link)');

// Get raw energyfiend.com data set
echo 'Downloading energyfiend.com data set', PHP_EOL;
$file = dirname(__FILE__) . '/the-caffeine-database.html';
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
    if (stripos($name, 'decaf') !== false) {
        continue;
    }
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
