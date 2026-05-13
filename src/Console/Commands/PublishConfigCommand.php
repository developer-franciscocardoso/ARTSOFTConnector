<?php

declare(strict_types=1);

namespace FranciscoCardoso\ArtsoftConnector\Console\Commands;

use FranciscoCardoso\ArtsoftConnector\ServiceProviders\ArtsoftConnectorServiceProvider;
use Illuminate\Console\Command;

class PublishConfigCommand extends Command
{
    protected $signature = 'artsoft:publish
                            {--force : Overwrite the config file if it already exists}';

    protected $description = 'Publish the ARTSOFT connector config file to config/artsoft.php';

    public function handle(): int
    {
        $targetPath = config_path('artsoft.php');

        $provider = new ArtsoftConnectorServiceProvider();

        try {
            $provider->publishConfig($targetPath, (bool) $this->option('force'));
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Config file published to ' . $targetPath);

        return self::SUCCESS;
    }
}
