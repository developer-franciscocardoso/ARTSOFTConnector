<?php

declare(strict_types=1);

namespace Feijosul\ArtsoftConnector\Services;

use Feijosul\ArtsoftConnector\Contracts\ArtsoftServiceInterface;
use Feijosul\ArtsoftConnector\Contracts\ConnectorInterface;
use Feijosul\ArtsoftConnector\DTO\Output\RequestResultDTO;
use Feijosul\ArtsoftConnector\Exceptions\ConfigurationException;
use Feijosul\ArtsoftConnector\Exceptions\ConnectionException;
use Feijosul\ArtsoftConnector\Support\LegacyConnectorAdapter;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class ArtsoftService implements ArtsoftServiceInterface
{
    private const XML_HEADER = '<?xml version="1.0" encoding="UTF-8" ?>';

    private const ERROR_PATTERNS = [
        '/<error>(.*?)<\/error>/is',
        '/<Error>(.*?)<\/Error>/is',
        '/<message>(.*?)<\/message>/is',
        '/<Message>(.*?)<\/Message>/is',
        '/<exception>(.*?)<\/exception>/is',
    ];

    private const NON_RETRYABLE_ERRORS = [
        'invalid credentials',
        'authentication failed',
        'access denied',
        'permission denied',
        'invalid company',
        'empresa não encontrada',
    ];

    private ConnectorInterface $connector;
    private string $currentCompany;
    private LoggerInterface $logger;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        private readonly array $config,
        ?string $company = null,
        ?LoggerInterface $logger = null
    ) {
        $this->logger = $logger ?? new NullLogger();
        $this->currentCompany = $company ?? (string) ($this->config['default_company'] ?? '');
        $this->validateCompany();
        $this->initializeConnection();
    }

    public function request(string $service, string $xml, bool $end = false): RequestResultDTO
    {
        try {
            $fullXml = self::XML_HEADER . $xml;
            $response = $this->connector->send($service, $fullXml, $end);

            return $this->processResponse($response, $service);
        } catch (\Throwable $e) {
            $this->logger->error('Artsoft request failed', [
                'service' => $service,
                'company' => $this->currentCompany,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResult($e->getMessage(), $service);
        }
    }

    public function requestWithRetry(string $service, string $xml, ?int $maxAttempts = null): RequestResultDTO
    {
        $attempts = max(1, $maxAttempts ?? (int) ($this->config['options']['retry_attempts'] ?? 3));
        $delayMs = (int) ($this->config['options']['retry_delay'] ?? 1000);
        $result = $this->errorResult('Request não executado', $service);

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            $result = $this->request($service, $xml);

            if ($result->success || $this->shouldNotRetry((string) $result->error)) {
                return $result;
            }

            if ($attempt < $attempts) {
                usleep($delayMs * 1000);
            }
        }

        return $result;
    }

    public function switchCompany(string $company): void
    {
        if ($company === $this->currentCompany) {
            return;
        }

        $this->currentCompany = $company;
        $this->validateCompany();
        $this->initializeConnection();
    }

    public function testConnection(): bool
    {
        return $this->request('ArtDB/_DbTables', '<root/>')->success;
    }

    public function getCurrentCompany(): string
    {
        return $this->currentCompany;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getAvailableCompanies(): array
    {
        return array_filter(
            $this->config['companies'] ?? [],
            static fn(array $company): bool => (bool) ($company['enabled'] ?? false)
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        return array_diff_key($this->config, ['password' => true]);
    }

    public function isConnected(): bool
    {
        return isset($this->connector);
    }

    /**
     * @deprecated Use request()
     * @return array<string, mixed>
     */
    public function doRequest(string $service, string $xml, bool $end = false): array
    {
        return $this->request($service, $xml, $end)->toArray();
    }

    /**
     * @deprecated Use requestWithRetry()
     * @return array<string, mixed>
     */
    public function doRequestWithRetry(string $service, string $xml, int $maxAttempts = null): array
    {
        return $this->requestWithRetry($service, $xml, $maxAttempts)->toArray();
    }

    private function validateCompany(): void
    {
        $companies = $this->config['companies'] ?? [];

        if (!isset($companies[$this->currentCompany])) {
            throw new ConfigurationException("Empresa {$this->currentCompany} não encontrada na configuração");
        }

        $company = $companies[$this->currentCompany];

        if (!(bool) ($company['enabled'] ?? false)) {
            throw new ConfigurationException("Empresa {$this->currentCompany} não está habilitada");
        }

        if (empty($company['db']) || empty($company['port'])) {
            throw new ConfigurationException("Configuração incompleta para a empresa {$this->currentCompany}");
        }
    }

    private function initializeConnection(): void
    {
        try {
            $this->connector = LegacyConnectorAdapter::fromPackageConfig($this->config, $this->currentCompany);
        } catch (\Throwable $e) {
            throw new ConnectionException(
                "Erro ao conectar ao Artsoft ({$this->currentCompany}): {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    private function processResponse(mixed $response, string $service): RequestResultDTO
    {
        if ($response === false) {
            return $this->errorResult('Artsoft returned false response', $service);
        }

        if (is_string($response)) {
            return $this->parseStringResponse($response, $service);
        }

        if (is_array($response)) {
            return $this->parseArrayResponse($response, $service);
        }

        return $this->errorResult('Tipo de resposta inesperado do Artsoft: ' . gettype($response), $service);
    }

    private function parseStringResponse(string $response, string $service): RequestResultDTO
    {
        $isError = str_contains($response, '<error>')
            || str_contains($response, '<Error>')
            || str_contains($response, 'error="')
            || str_contains($response, 'err=')
            || str_contains($response, 'Status 10101')
            || str_contains($response, '<exception>');

        if ($isError) {
            return $this->errorResult($this->extractErrorMessage($response), $service, $response);
        }

        return $this->successResult($response, $service);
    }

    /**
     * @param array<string, mixed> $response
     */
    private function parseArrayResponse(array $response, string $service): RequestResultDTO
    {
        $error = $response['error'] ?? $response['Error'] ?? null;

        if ($error !== null) {
            return $this->errorResult((string) $error, $service, $response);
        }

        return $this->successResult($response, $service);
    }

    private function extractErrorMessage(string $xmlResponse): string
    {
        foreach (self::ERROR_PATTERNS as $pattern) {
            if (preg_match($pattern, $xmlResponse, $matches)) {
                return trim($matches[1]);
            }
        }

        return 'Erro no Artsoft. Resposta: ' . substr($xmlResponse, 0, 100) . '...';
    }

    private function successResult(mixed $data, string $service): RequestResultDTO
    {
        return new RequestResultDTO(
            success: true,
            service: $service,
            company: $this->currentCompany,
            timestamp: date('c'),
            data: $data
        );
    }

    private function errorResult(string $error, string $service, mixed $rawResponse = null): RequestResultDTO
    {
        return new RequestResultDTO(
            success: false,
            service: $service,
            company: $this->currentCompany,
            timestamp: date('c'),
            error: $error,
            rawResponse: $rawResponse
        );
    }

    private function shouldNotRetry(string $error): bool
    {
        foreach (self::NON_RETRYABLE_ERRORS as $phrase) {
            if (stripos($error, $phrase) !== false) {
                return true;
            }
        }

        return false;
    }
}
