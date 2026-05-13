# francisco-cardoso/artsoft-connector

Framework-agnostic PHP package for connecting to ARTSOFT.

## Requirements

- PHP 8.2+
- ext-curl
- ext-simplexml

## Install

```bash
composer require francisco-cardoso/artsoft-connector
```

For local workspace development (for example from ARTSOFTCustomer), use a path repository:

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "../ARTSOFTConnector",
      "options": {
        "symlink": true
      }
    }
  ],
  "require": {
    "francisco-cardoso/artsoft-connector": "^1.0"
  }
}
```

## Config

The package ships with a reusable PHP config file at `config/artsoft.php`.

If you want to copy that file into your own application, use the framework-agnostic publisher helper:

```php
<?php

declare(strict_types=1);

use FranciscoCardoso\ArtsoftConnector\ServiceProviders\ArtsoftConnectorServiceProvider;

require __DIR__ . '/vendor/autoload.php';

$provider = new ArtsoftConnectorServiceProvider();
$provider->publishConfig(__DIR__ . '/config/artsoft.php');
```

You can also load the bundled file directly with `Artsoft::fromConfigFile()` or require your own copied config file and pass the resulting array to `Artsoft::create()`.

## Usage

```php
<?php

declare(strict_types=1);

use FranciscoCardoso\ArtsoftConnector\Artsoft;

require __DIR__ . '/vendor/autoload.php';

$config = require __DIR__ . '/config/artsoft.php';
$service = Artsoft::create($config);

$result = $service->request('ArtDB/_DbTables', '<root/>');
var_dump($result->toArray());
```

## Main API

- `FranciscoCardoso\ArtsoftConnector\Artsoft::create()`
- `FranciscoCardoso\ArtsoftConnector\Contracts\ArtsoftServiceInterface`
- `FranciscoCardoso\ArtsoftConnector\Services\ArtsoftService`
- `FranciscoCardoso\ArtsoftConnector\DTO\Output\RequestResultDTO`

## Tooling

```bash
vendor/bin/phpunit --configuration phpunit.xml
vendor/bin/phpstan analyse -c phpstan.neon
```

## Documentation

- Full usage and expected example outputs: `docs/usage-and-examples.md`
- Runnable example scripts: `examples/basic.php`, `examples/advanced.php`, `examples/custom_connector.php`
