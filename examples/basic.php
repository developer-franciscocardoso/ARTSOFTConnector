<?php

declare(strict_types=1);

use FranciscoCardoso\ArtsoftConnector\Artsoft;

require __DIR__ . '/../vendor/autoload.php';

// Usage:
// 1) Configure credentials in config/artsoft.php
// 2) Run: php examples/basic.php
// Expected output:
// - An array dumped to stdout with the normalized API response data.
// - On failure, an exception from the connector/service layer.

$config = require __DIR__ . '/../config/artsoft.php';
$service = Artsoft::create($config);

$result = $service->request('ArtDB/_DbTables', '<root/>');

echo "Basic example response:\n";
var_dump($result->toArray());
