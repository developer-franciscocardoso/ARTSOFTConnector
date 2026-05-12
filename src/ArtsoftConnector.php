<?php

declare(strict_types=1);

namespace FranciscoCardoso\ArtsoftConnector;

use CurlHandle;
use Exception;
use FranciscoCardoso\ArtsoftConnector\Contracts\ConnectorInterface;

final class ArtsoftConnector implements ConnectorInterface
{
    /** @var array<int, array<string, scalar|null>> */
    private array $servers;

    private int $activeServerIndex = 0;
    private string $host;
    private int $port;
    private string $username;
    private string $password;
    private string $dbName = '';
    private string $workDate = '';

    private bool $useFormattedDbWildcard = false;
    private bool $useEncryption = false;
    private bool $useGzip = false;
    private string $hashAlgorithm = 'SHA1';
    private int $xmlIndent = 2;

    private bool $loggedIn = false;
    private int $requestCounter = 0;
    private string $challenge = '';

    /** @var array<string, string> */
    private array $requestHeaders = [];

    /** @var array<string, string> */
    private array $responseHeaders = [];

    /** @var array<int, mixed> */
    private array $curlOptions = [];

    private CurlHandle $connection;

    /**
     * @param array<int, array<string, scalar|null>> $servers
     * @param array<string, mixed> $options
     *
     * @throws Exception
     */
    public function __construct(array $servers = [], array $options = [])
    {
        date_default_timezone_set('UTC');

        if (!function_exists('curl_version')) {
            throw new Exception('A extensão CURL não está activada no PHP.', 1);
        }

        if ($servers === []) {
            throw new Exception('Tem que ser fornecido pelo menos um servidor.', 1);
        }

        $this->servers = $servers;
        $this->applyOptions($options);
        $this->useServer(0);

        $connection = curl_init();
        if (!$connection instanceof CurlHandle) {
            throw new Exception('Não foi possível inicializar a ligação CURL.', 1);
        }

        $this->connection = $connection;
        $this->curlOptions = [
            CURLOPT_POST => 1,
            CURLOPT_PORT => $this->port,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 3600,
            CURLOPT_RETURNTRANSFER => 1,
        ];
    }

    public function __destruct()
    {
        $this->requestCounter = 0;
        $this->loggedIn = false;
        curl_close($this->connection);
    }

    public function isLogged(): bool
    {
        return $this->loggedIn;
    }

    /** @return array<string, mixed> */
    public function getInfo(): array
    {
        /** @var array<string, mixed> $info */
        $info = curl_getinfo($this->connection);
        return $info;
    }

    public function getParameterInfo(int $option): mixed
    {
        return curl_getinfo($this->connection, $option);
    }

    public function getErrorNumber(): int
    {
        return curl_errno($this->connection);
    }

    public function getErrorMessage(): string
    {
        return curl_error($this->connection);
    }

    public function send(string $service, string $payload, bool $end = false): string|array|false
    {
        return $this->doRequest($service, $payload, $end);
    }

    public function doRequest(string $service, string $payload = '', bool $end = true): string
    {
        if (!$this->isLogged()) {
            $this->doLogin();
        }

        $this->requestHeaders['Content-Type'] = $this->useEncryption ? 'bin/xml' : 'text/xml';
        $this->requestHeaders['XmlIndent'] = (string) $this->xmlIndent;

        if ($end) {
            unset($this->requestHeaders['Connection'], $this->requestHeaders['Keep-Alive']);
            $this->loggedIn = false;
        }

        if ($this->requestCounter >= 2) {
            unset($this->requestHeaders['digest']);
        }

        $this->requestCounter++;

        $rawResponse = $this->sendRequest($service, $payload);
        if (!is_string($rawResponse)) {
            throw new Exception('Resposta inválida do servidor.');
        }

        $headerSize = (int) curl_getinfo($this->connection, CURLINFO_HEADER_SIZE);
        $responseBody = (string) substr($rawResponse, $headerSize);

        if (($this->responseHeaders['Status'] ?? '') !== '200') {
            $message = $responseBody !== '' ? utf8_encode($responseBody) : 'Login inválido';
            throw new Exception($message);
        }

        $gzipAccepted = $this->useGzip && str_contains($this->responseHeaders['Accept-Encoding'] ?? '', 'gzip');
        if ($this->useEncryption && (($this->responseHeaders['Content-Type'] ?? '') === 'text/xml; bin')) {
            return $this->decodeResponse($responseBody, $gzipAccepted);
        }

        if ($gzipAccepted) {
            $decoded = gzdecode($responseBody);
            return is_string($decoded) ? $decoded : $responseBody;
        }

        return $responseBody;
    }

    /**
     * @param string|false $payload
     * @return string|false
     */
    public function sendRequest(string $service = '', string|false $payload = false): string|false
    {
        $response = false;

        $this->curlOptions[CURLOPT_URL] = sprintf('%s/%s', $this->host, $service);

        if ($payload !== false && $payload !== '') {
            if ($this->useGzip && str_contains($this->responseHeaders['Accept-Encoding'] ?? '', 'gzip')) {
                $this->requestHeaders['Content-Encoding'] = 'gzip';
                $encoded = gzencode($payload);
                if ($encoded !== false) {
                    $payload = $encoded;
                }
            }

            if ($this->useEncryption) {
                $this->requestHeaders['Checksum'] = $this->calculateChecksum($payload);
                $payload = $this->encodeRequest($payload);
            }

            $this->curlOptions[CURLOPT_POSTFIELDS] = $payload;
        }

        $this->curlOptions[CURLOPT_HTTPHEADER] = $this->buildHttpHeaders();
        curl_setopt_array($this->connection, $this->curlOptions);

        $executed = curl_exec($this->connection);
        if (is_string($executed)) {
            $response = $executed;
            $headerSize = (int) curl_getinfo($this->connection, CURLINFO_HEADER_SIZE);
            $rawHeaders = (string) substr($response, 0, $headerSize);
            $this->parseHeaders($rawHeaders);
        }

        if ($this->getErrorNumber() !== CURLE_OK && $this->getErrorNumber() === 7) {
            throw new Exception('Não foi possível conectar ao servidor.', 1);
        }

        return $response;
    }

    /** @throws Exception */
    public function doLogin(): void
    {
        if ($this->requestCounter !== 0) {
            return;
        }

        $this->curlOptions[CURLOPT_HEADER] = 1;
        $this->curlOptions[CURLOPT_POST] = 0;
        $this->requestHeaders['Connection'] = 'Keep-Alive';
        $this->requestHeaders['Keep-Alive'] = '30';
        $this->requestHeaders['name'] = $this->username;

        if ($this->dbName !== '') {
            $this->requestHeaders['DBName'] = $this->dbName;
        }
        if ($this->workDate !== '') {
            $this->requestHeaders['workDate'] = $this->workDate;
        }

        $this->requestCounter++;
        $loginResponse = $this->sendRequest('login');

        if ($loginResponse !== false) {
            $challenge = $this->getChallenge($loginResponse);
            if ($challenge !== false) {
                $this->challenge = $challenge;
                $this->requestHeaders['digest'] = $this->buildLoginDigest();
                $this->loggedIn = true;
            } else {
                $this->switchServer();
            }
        } else {
            $this->switchServer();
        }

        unset($this->requestHeaders['name']);
        $this->curlOptions[CURLOPT_HEADER] = 1;
        $this->curlOptions[CURLOPT_POST] = 1;
    }

    /** @throws Exception */
    private function switchServer(): void
    {
        $this->activeServerIndex++;

        if (!array_key_exists($this->activeServerIndex, $this->servers)) {
            throw new Exception('Não foi possível conectar ao servidor.', 1);
        }

        $this->useServer($this->activeServerIndex);
        $this->curlOptions[CURLOPT_PORT] = $this->port;
        $this->requestCounter = 0;
        $this->doLogin();
    }

    /**
     * @param array<string, mixed> $options
     */
    private function applyOptions(array $options): void
    {
        $this->useEncryption = (bool) ($options['encrypt'] ?? false);
        $this->useGzip = (bool) ($options['gzip'] ?? false);
        $this->hashAlgorithm = (string) ($options['hash'] ?? 'SHA1');
        $this->xmlIndent = (int) ($options['indent'] ?? 2);

        if ((bool) ($options['formation'] ?? false)) {
            $this->useFormattedDbWildcard = true;
            $this->dbName = '*';
        }
    }

    /**
     * @throws Exception
     */
    private function useServer(int $index): void
    {
        /** @var array<string, scalar|null> $server */
        $server = $this->servers[$index];

        $this->host = (string) ($server['host'] ?? '');
        $this->port = (int) ($server['port'] ?? 0);
        $this->username = (string) ($server['username'] ?? '');
        $this->password = (string) ($server['password'] ?? '');

        if ($this->host === '' || $this->port === 0) {
            throw new Exception('Configuração de servidor inválida.', 1);
        }

        if (!$this->useFormattedDbWildcard && isset($server['db']) && (string) $server['db'] !== '') {
            $this->dbName = (string) $server['db'];
        }

        if (isset($server['year']) && (string) $server['year'] !== '') {
            $this->workDate = (string) $server['year'];
        }
    }

    /** @return list<string> */
    private function buildHttpHeaders(): array
    {
        $headers = [];
        foreach ($this->requestHeaders as $name => $value) {
            $headers[] = $name . ': ' . $value;
        }
        return $headers;
    }

    private function parseHeaders(string $rawHeaders): void
    {
        $this->responseHeaders = [];

        foreach (explode("\r\n", $rawHeaders) as $headerLine) {
            if ($headerLine === '') {
                continue;
            }

            if (!str_contains($headerLine, ':')) {
                if (str_contains($headerLine, 'HTTP/1.1')) {
                    $parts = explode(' ', $headerLine);
                    $this->responseHeaders['Status'] = (string) ($parts[1] ?? '');
                }
                continue;
            }

            [$name, $value] = explode(':', $headerLine, 2);
            $this->responseHeaders[trim($name)] = trim($value);
        }
    }

    private function encodeRequest(string $request): string
    {
        return $this->artsoftCrypt($request);
    }

    private function decodeResponse(string $response, bool $gzip): string
    {
        $decoded = $this->artsoftCrypt($response);

        if ($gzip) {
            $gzipDecoded = gzdecode($decoded);
            if (is_string($gzipDecoded)) {
                $decoded = $gzipDecoded;
            }
        }

        $xml = simplexml_load_string($decoded);
        if ($xml === false) {
            return $decoded;
        }

        $asXml = $xml->asXML();
        return is_string($asXml) ? $asXml : $decoded;
    }

    private function artsoftCrypt(string $payload): string
    {
        $key = sha1(sha1($this->password, true) . sha1($this->challenge, true), true);
        return $this->xorCipher($payload, $key);
    }

    private function calculateChecksum(string $payload): string
    {
        $sum = 0;
        $length = strlen($payload);

        for ($i = 0; $i < $length; $i++) {
            $sum += ord($payload[$i]);
        }

        return dechex($sum);
    }

    private function getChallenge(string $response): string|false
    {
        $lines = explode("\r\n", $response);

        foreach ($lines as $line) {
            $digestLine = stristr($line, 'Digest:');
            if ($digestLine !== false) {
                return trim(substr($digestLine, 8));
            }
        }

        return false;
    }

    private function buildLoginDigest(): string
    {
        return strtoupper($this->hashAlgorithm) === 'MD5'
            ? $this->md5DoubleDigest()
            : $this->sha1DoubleDigest();
    }

    private function md5DoubleDigest(): string
    {
        return md5(md5($this->username, true) . md5($this->password, true) . md5($this->challenge, true));
    }

    private function sha1DoubleDigest(): string
    {
        return sha1(sha1($this->username, true) . sha1($this->password, true) . sha1($this->challenge, true));
    }

    private function xorCipher(string $input, string $key): string
    {
        $keyLength = strlen($key);
        $inputLength = strlen($input);

        for ($i = 0; $i < $inputLength; $i++) {
            $keyIndex = $i % $keyLength;
            $input[$i] = chr(ord($input[$i]) ^ ord($key[$keyIndex]));
        }

        return $input;
    }
}
