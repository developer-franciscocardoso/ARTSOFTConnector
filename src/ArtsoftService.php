<?php

declare(strict_types=1);

namespace Feijosul\ArtsoftConnector;

use Feijosul\ArtsoftConnector\Services\ArtsoftService as BaseArtsoftService;
use Psr\Log\LoggerInterface;

/**
 * @deprecated Use Feijosul\ArtsoftConnector\Services\ArtsoftService instead.
 */
class ArtsoftService extends BaseArtsoftService
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(array $config, ?string $company = null, ?LoggerInterface $logger = null)
    {
        parent::__construct($config, $company, $logger);
    }
}
