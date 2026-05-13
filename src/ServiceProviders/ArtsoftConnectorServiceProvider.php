<?php

declare(strict_types=1);

namespace FranciscoCardoso\ArtsoftConnector\ServiceProviders;

use RuntimeException;

final class ArtsoftConnectorServiceProvider
{
    public function __construct(
        private readonly string $sourceConfigPath = __DIR__ . '/../../config/artsoft.php'
    ) {}

    public function getSourceConfigPath(): string
    {
        return $this->sourceConfigPath;
    }

    public function publishConfig(string $targetPath, bool $overwrite = false): string
    {
        if (!is_file($this->sourceConfigPath)) {
            throw new RuntimeException(sprintf('Config file not found at %s', $this->sourceConfigPath));
        }

        $targetDirectory = dirname($targetPath);
        if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0777, true) && !is_dir($targetDirectory)) {
            throw new RuntimeException(sprintf('Unable to create config directory at %s', $targetDirectory));
        }

        if (is_file($targetPath) && !$overwrite) {
            throw new RuntimeException(sprintf('Config file already exists at %s', $targetPath));
        }

        if (!copy($this->sourceConfigPath, $targetPath)) {
            throw new RuntimeException(sprintf('Unable to publish config file to %s', $targetPath));
        }

        return $targetPath;
    }
}
