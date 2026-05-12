<?php

declare(strict_types=1);

namespace FranciscoCardoso\ArtsoftConnector;

use FranciscoCardoso\ArtsoftConnector\Contracts\ArtsoftServiceInterface;
use FranciscoCardoso\ArtsoftConnector\Services\ArtsoftService;
use Psr\Log\LoggerInterface;

final class Artsoft
{
    /**
     * @param array<string, mixed> $config
     */
    public static function create(array $config, ?string $company = null, ?LoggerInterface $logger = null): ArtsoftServiceInterface
    {
        return new ArtsoftService($config, $company, $logger);
    }

    /**
     * @param non-empty-string $configFile
     */
    public static function fromConfigFile(string $configFile, ?string $company = null, ?LoggerInterface $logger = null): ArtsoftServiceInterface
    {
        $config = require $configFile;
        if (!is_array($config)) {
            throw new \RuntimeException('Config file must return an array.');
        }

        return self::create($config, $company, $logger);
    }
}
