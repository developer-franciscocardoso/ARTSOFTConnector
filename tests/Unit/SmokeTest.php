<?php

declare(strict_types=1);

namespace Feijosul\ArtsoftConnector\Tests\Unit;

use Feijosul\ArtsoftConnector\Artsoft;
use Feijosul\ArtsoftConnector\Contracts\ArtsoftServiceInterface;
use PHPUnit\Framework\TestCase;

final class SmokeTest extends TestCase
{
    public function testFactoryCreatesService(): void
    {
        $config = [
            'host' => 'localhost',
            'username' => 'admin',
            'password' => 'secret',
            'default_company' => 'Demo2026',
            'options' => [
                'encrypt' => true,
            ],
            'companies' => [
                'Demo2026' => [
                    'db' => 'DB',
                    'port' => '2026',
                    'enabled' => true,
                ],
            ],
        ];

        $service = Artsoft::create($config);
        self::assertInstanceOf(ArtsoftServiceInterface::class, $service);
    }
}
