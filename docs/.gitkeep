<?php

declare(strict_types=1);

use FranciscoCardoso\ArtsoftConnector\Contracts\ConnectorInterface;

require __DIR__ . '/../vendor/autoload.php';

// Usage:
// 1) Run: php examples/custom_connector.php
// 2) Replace InMemoryConnector with your real ConnectorInterface implementation.
// Expected output:
// - A local mock payload array proving your connector send() contract shape.

final class InMemoryConnector implements ConnectorInterface
{
    public function send(string $service, string $payload, bool $end = false): string|array|false
    {
        return [
            'service' => $service,
            'payload' => $payload,
            'end' => $end,
            'status' => 'ok',
        ];
    }
}

$connector = new InMemoryConnector();
echo "Custom connector response:\n";
var_dump($connector->send('demo', '<root/>'));
