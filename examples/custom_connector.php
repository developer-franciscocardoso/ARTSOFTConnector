<?php

declare(strict_types=1);

use Feijosul\ArtsoftConnector\Contracts\ConnectorInterface;

require __DIR__ . '/../vendor/autoload.php';

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
var_dump($connector->send('demo', '<root/>'));
