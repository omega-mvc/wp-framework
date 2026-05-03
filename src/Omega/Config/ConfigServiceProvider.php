<?php

declare(strict_types=1);

namespace Omega\Config;

use Omega\Container\ServiceProvider;

use function basename;
use function glob;
use function is_dir;

class ConfigServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('config', function () {
            $configPath = $this->app->getBasePath() . '/config';
            $config     = [];
            if (is_dir($configPath)) {
                foreach (glob($configPath . '/*.php') as $file) {
                    $key          = basename($file, '.php');
                    $config[$key] = require $file;
                }
            }

            return new ConfigRepository($config);
        });

        $this->app->singleton('settings', function ($app) {
            return new SettingsRepository($app);
        });
    }
}
