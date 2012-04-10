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
 * @copyright 2008-2011 Phergie Development Team (http://phergie.org)
 * @license   http://phergie.org/license New BSD License
 * @link      http://pear.phergie.org/package/Phergie
 */

// Create database schema
echo 'Creating database', PHP_EOL;
$file = dirname(__FILE__) . '/cocktail.db';
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
    $file = dirname(__FILE__) . '/' . $start . '.html';
    if (file_exists($file)) {
        continue;
    }

    $params = array(
        'level' => 2,
        'dir'   => 'drinks',
        'char'  => '%2A',
        'start' => $start
    );
    copy(
        sprintf('http://www.webtender.com/db/browse?%s', implode('&', $params)),
        $file
    );

    if (!isset($limit)) {
        $contents = file_get_contents($file);
        preg_match('/([0-9]+) found/', $contents, $match);
        $limit = $match[1] + (150 - ($match[1] % 150));
    }

    printf(
        'Got records %d - %d of %d' . PHP_EOL,
        $start, min($start + 150, $limit), $limit
    );
    $start += 150;
} while ($start < $limit);

// Extract data from data set
$start = 1;
while ($start < $limit) {
    printf(
        'Processing %d - %d of %d' . PHP_EOL,
        $start, min($start + 150, $limit), $limit
    );

    $file = dirname(__FILE__) . '/' . $start . '.html';
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
    $file = dirname(__FILE__) . '/' . $start . '.html';
    unlink($file);
    $start += 150;
}
