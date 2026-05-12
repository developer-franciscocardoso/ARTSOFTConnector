<?php

declare(strict_types=1);

namespace FranciscoCardoso\ArtsoftConnector\Support;

use FranciscoCardoso\ArtsoftConnector\ArtsoftConnector;
use FranciscoCardoso\ArtsoftConnector\Contracts\ConnectorInterface;
use FranciscoCardoso\ArtsoftConnector\Exceptions\ConfigurationException;

final class LegacyConnectorAdapter implements ConnectorInterface
{
    private ArtsoftConnector $legacyConnector;

    /**
     * @param array<string, scalar> $server
     * @param array<string, mixed> $options
     */
    public function __construct(array $server, array $options = [])
    {
        $this->legacyConnector = new ArtsoftConnector([$server], $options);
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function fromPackageConfig(array $config, string $company): self
    {
        $companies = $config['companies'] ?? [];
        if (!is_array($companies) || !isset($companies[$company]) || !is_array($companies[$company])) {
            throw new ConfigurationException("Empresa {$company} não encontrada na configuração");
        }

        $companyConfig = $companies[$company];

        $server = [
            'year' => date('Y') . '0101',
            'host' => (string) ($config['host'] ?? 'localhost'),
            'username' => (string) ($config['username'] ?? ''),
            'password' => (string) ($config['password'] ?? ''),
            'db' => (string) ($companyConfig['db'] ?? ''),
            'port' => (string) ($companyConfig['port'] ?? ''),
        ];

        if ($server['db'] === '' || $server['port'] === '') {
            throw new ConfigurationException("Configuração incompleta para a empresa {$company}");
        }

        $options = $config['options'] ?? [];

        return new self($server, is_array($options) ? $options : []);
    }

    public function send(string $service, string $payload, bool $end = false): string|array|false
    {
        return $this->legacyConnector->doRequest($service, $payload, $end);
    }
}
