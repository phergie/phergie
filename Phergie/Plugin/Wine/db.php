<?php

// Create database schema
echo 'Creating database', PHP_EOL;
$file = __DIR__ . '/wine.db';
if (file_exists($file)) {
    unlink($file);
}
$db = new PDO('sqlite:' . $file);
$db->exec('CREATE TABLE wine (name VARCHAR(255), link VARCHAR(255))');
$db->exec('CREATE UNIQUE INDEX wine_name ON wine (name)');
$insert = $db->prepare('INSERT INTO wine (name, link) VALUES (:name, :link)');

// Get and decompress lcboapi.com data set
$outer = __DIR__ . '/current.zip';
if (!file_exists($outer)) {
    echo 'Downloading lcboapi.com data set', PHP_EOL;
    copy('http://lcboapi.com/download/current.zip', $outer);
}

echo 'Decompressing lcboapi.com data set', PHP_EOL;
$zip = new ZipArchive;
$zip->open($outer);
$stat = $zip->statIndex(0);
$inner = __DIR__ . '/' . $stat['name'];
$zip->extractTo(__DIR__);
$zip->close();
$zip = new ZipArchive;
$zip->open($inner);
$stat = $zip->statIndex(0);
$file = __DIR__ . '/' . $stat['name'];
$zip->extractTo(__DIR__);
$zip->close();

// Aggregate data set into the database
$lcbo = new PDO('sqlite:' . $file);
$result = $lcbo->query('SELECT product_no, name FROM products WHERE primary_category = "Wine"');
$wines = $result->fetchAll();
echo 'Processing lcboapi.com data - ', number_format(count($wines), 0), ' records', PHP_EOL;
$db->beginTransaction();
foreach ($wines as $wine) {
    $name = $wine['name'];
    $link = 'http://lcboapi.com/products/' . $wine['product_no'];
    $insert->execute(array($name, $link));
}
$db->commit();

// Clean up
echo 'Cleaning up', PHP_EOL;
unset($lcbo);
unlink($outer);
unlink($inner);
unlink($file);
