# Usage And Examples

This package is published as `francisco-cardoso/artsoft-connector`.

## Install

From Packagist:

```bash
composer require francisco-cardoso/artsoft-connector
```

For local development with sibling packages (for example, ARTSOFTCustomer):

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

## Namespace Note

The package name is `francisco-cardoso/artsoft-connector`, and the PHP namespace used by classes is `FranciscoCardoso\\ArtsoftConnector`.

## Basic Usage

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

Expected output:

- A dumped PHP array containing the normalized ARTSOFT response payload.
- If the request fails, an exception is thrown.

## Running Included Examples

From package root:

```bash
php examples/basic.php
php examples/advanced.php
php examples/custom_connector.php
```

Expected outputs:

- `examples/basic.php`: a labeled response array from a standard request.
- `examples/advanced.php`: a labeled response array after retry-enabled request execution.
- `examples/custom_connector.php`: a labeled mock array showing the expected `ConnectorInterface::send()` return shape.
