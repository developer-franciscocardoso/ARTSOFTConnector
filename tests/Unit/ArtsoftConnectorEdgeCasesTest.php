<?php

declare(strict_types=1);

namespace FranciscoCardoso\ArtsoftConnector\Tests\Unit;

use Exception;
use FranciscoCardoso\ArtsoftConnector\ArtsoftConnector;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class ArtsoftConnectorEdgeCasesTest extends TestCase
{
    public function testDecodeResponseHandlesEncryptedAndGzippedPayload(): void
    {
        $connector = $this->createConnector();

        $this->setPrivate($connector, 'password', 'secret');
        $this->setPrivate($connector, 'challenge', 'challenge-123');

        $xml = '<root>ok</root>';
        $gzippedXml = gzencode($xml);
        self::assertIsString($gzippedXml);

        $encrypted = $this->invokePrivate($connector, 'artsoftCrypt', [$gzippedXml]);
        self::assertIsString($encrypted);

        $decoded = $this->invokePrivate($connector, 'decodeResponse', [$encrypted, true]);
        self::assertIsString($decoded);
        self::assertStringContainsString('<root>ok</root>', $decoded);
    }

    public function testGetChallengeReturnsFalseWhenDigestHeaderIsMissing(): void
    {
        $connector = $this->createConnector();

        $response = "HTTP/1.1 200 OK\r\nServer: test\r\n\r\n";
        $challenge = $this->invokePrivate($connector, 'getChallenge', [$response]);

        self::assertFalse($challenge);
    }

    public function testGetChallengeExtractsDigestValue(): void
    {
        $connector = $this->createConnector();

        $response = "HTTP/1.1 200 OK\r\nDigest: abc123\r\n\r\n";
        $challenge = $this->invokePrivate($connector, 'getChallenge', [$response]);

        self::assertSame('abc123', $challenge);
    }

    public function testSwitchServerThrowsWhenNoFallbackServerExists(): void
    {
        $connector = $this->createConnector();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Não foi possível conectar ao servidor.');

        $this->invokePrivate($connector, 'switchServer');
    }

    private function createConnector(): ArtsoftConnector
    {
        return new ArtsoftConnector([
            [
                'host' => 'http://127.0.0.1',
                'port' => 65535,
                'username' => 'admin',
                'password' => 'secret',
                'db' => 'DB',
                'year' => '20260101',
            ],
        ], [
            'encrypt' => true,
            'gzip' => true,
            'hash' => 'SHA1',
        ]);
    }

    /**
     * @param list<mixed> $args
     */
    private function invokePrivate(object $object, string $method, array $args = []): mixed
    {
        $reflection = new ReflectionClass($object);
        $refMethod = $reflection->getMethod($method);
        $refMethod->setAccessible(true);

        return $refMethod->invokeArgs($object, $args);
    }

    private function setPrivate(object $object, string $property, mixed $value): void
    {
        $reflection = new ReflectionClass($object);
        $refProperty = $reflection->getProperty($property);
        $refProperty->setAccessible(true);
        $refProperty->setValue($object, $value);
    }
}
