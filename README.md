# feijosul/artsoft-connector

Framework-agnostic PHP package for connecting to ARTSOFT.

## Requirements

- PHP 8.2+
- ext-curl
- ext-simplexml

## Install

```bash
composer require feijosul/artsoft-connector
```

## Usage

```php
<?php

declare(strict_types=1);

use Feijosul\ArtsoftConnector\Artsoft;

require __DIR__ . '/vendor/autoload.php';

$config = require __DIR__ . '/config/artsoft.php';
$service = Artsoft::create($config);

$result = $service->request('ArtDB/_DbTables', '<root/>');
var_dump($result->toArray());
```

## Main API

- `Feijosul\ArtsoftConnector\Artsoft::create()`
- `Feijosul\ArtsoftConnector\Contracts\ArtsoftServiceInterface`
- `Feijosul\ArtsoftConnector\Services\ArtsoftService`
- `Feijosul\ArtsoftConnector\DTO\Output\RequestResultDTO`

## Tooling

```bash
vendor/bin/phpunit --configuration phpunit.xml
vendor/bin/phpstan analyse -c phpstan.neon
```
