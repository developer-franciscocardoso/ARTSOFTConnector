<?php

declare(strict_types=1);

use FranciscoCardoso\ArtsoftConnector\Artsoft;
use Psr\Log\NullLogger;

require __DIR__ . '/../vendor/autoload.php';

// Usage:
// 1) Configure credentials in config/artsoft.php
// 2) Run: php examples/advanced.php
// Expected output:
// - An array dumped to stdout with the response data.
// - Retries are attempted up to 3 times before an exception is thrown.

$config = require __DIR__ . '/../config/artsoft.php';
$service = Artsoft::create($config, null, new NullLogger());

$result = $service->requestWithRetry('ArtDB/_DbTables', '<root/>', 3);

echo "Advanced example response (with retries):\n";
var_dump($result->toArray());
