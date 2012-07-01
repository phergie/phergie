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

$dbFile = 'tld.db';

if (file_exists($dbFile)) {
    exit;
}

$db = new PDO('sqlite:' . dirname(__FILE__) . '/' . $dbFile);

$query = '
    CREATE TABLE tld (
        tld VARCHAR(20),
        type VARCHAR(20),
        description VARCHAR(255)
    )
';
$db->exec($query);

$insert = $db->prepare(
    'INSERT INTO tld (tld, type, description)
    VALUES (:tld, :type, :description)'
);

$contents = file_get_contents(
    'http://www.iana.org/domains/root/db/'
);

libxml_use_internal_errors(true);
$doc = new DOMDocument;
$doc->loadHTML($contents);
libxml_clear_errors();

$descriptions = array(
    'com' => 'Commercial',
    'info' => 'Information',
    'net' => 'Network',
    'org' => 'Organization',
    'edu' => 'Educational',
    'name' => 'Individuals, by name'
);

$xpath = new DOMXPath($doc);
$rows = $xpath->query('//tr[contains(@class, "iana-group")]');
foreach (range(0, $rows->length - 1) as $index) {
    $row = $rows->item($index);
    $cells = $xpath->query('.//td', $row);
    $tld = strtolower(ltrim($cells->item(0)->textContent, '.'));
    $type = $cells->item(1)->nodeValue;
    if (isset($descriptions[$tld])) {
        $description = $descriptions[$tld];
    } else {
        $description = $cells->item(2)->textContent;
        $regex = '{(^(?:Reserved|Restricted)\s*(?:exclusively\s*)?'
         . '(?:for|to)\s*(?:members of\s*)?(?:the|support)?'
         . '\s*|\s*as advised.*$)}i';
        $description = strip_tags(preg_replace($regex, '', $description));
        $description = ucfirst(trim($description));
    }
    $data = array_map(
        'html_entity_decode',
        array(
            'tld' => $tld,
            'type' => $type,
            'description' => $description
        )
    );
    $insert->execute($data);
}
