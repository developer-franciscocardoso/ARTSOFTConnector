<?php

declare(strict_types=1);

namespace FranciscoCardoso\ArtsoftConnector\Tests\Unit;

use FranciscoCardoso\ArtsoftConnector\ServiceProviders\ArtsoftConnectorServiceProvider;
use PHPUnit\Framework\TestCase;

final class ArtsoftConnectorServiceProviderTest extends TestCase
{
    public function testPublishConfigCopiesBundledConfigToTargetPath(): void
    {
        $provider = new ArtsoftConnectorServiceProvider();
        $sourcePath = $provider->getSourceConfigPath();

        $targetDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'artsoft-connector-' . uniqid('', true);
        $targetPath = $targetDirectory . DIRECTORY_SEPARATOR . 'artsoft.php';

        try {
            $publishedPath = $provider->publishConfig($targetPath);

            self::assertSame($targetPath, $publishedPath);
            self::assertFileExists($targetPath);
            self::assertSame(file_get_contents($sourcePath), file_get_contents($targetPath));
        } finally {
            if (is_file($targetPath)) {
                unlink($targetPath);
            }

            if (is_dir($targetDirectory)) {
                rmdir($targetDirectory);
            }
        }
    }
}
