<?php

declare(strict_types=1);

namespace Feijosul\ArtsoftConnector\Contracts;

use Feijosul\ArtsoftConnector\DTO\Output\RequestResultDTO;

interface ArtsoftServiceInterface
{
    public function request(string $service, string $xml, bool $end = false): RequestResultDTO;

    public function requestWithRetry(string $service, string $xml, ?int $maxAttempts = null): RequestResultDTO;

    public function switchCompany(string $company): void;

    public function testConnection(): bool;
}
