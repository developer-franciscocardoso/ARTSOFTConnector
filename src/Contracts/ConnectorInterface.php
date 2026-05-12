<?php

declare(strict_types=1);

namespace Feijosul\ArtsoftConnector\Contracts;

interface ConnectorInterface
{
    /**
     * @return array<string, mixed>|string|false
     */
    public function send(string $service, string $payload, bool $end = false): string|array|false;
}
