<?php

declare(strict_types=1);

use Feijosul\ArtsoftConnector\Artsoft;
use Psr\Log\NullLogger;

require __DIR__ . '/../vendor/autoload.php';

$config = require __DIR__ . '/../config/artsoft.php';
$service = Artsoft::create($config, null, new NullLogger());

$result = $service->requestWithRetry('ArtDB/_DbTables', '<root/>', 3);
var_dump($result->toArray());
