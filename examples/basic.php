<?php

declare(strict_types=1);

use Feijosul\ArtsoftConnector\Artsoft;

require __DIR__ . '/../vendor/autoload.php';

$config = require __DIR__ . '/../config/artsoft.php';
$service = Artsoft::create($config);

$result = $service->request('ArtDB/_DbTables', '<root/>');
var_dump($result->toArray());
