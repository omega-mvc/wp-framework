<?php

namespace Omega\Container;

use Omega\Application\Application;

defined( 'ABSPATH' ) || exit;

class ServiceProvider
{
    public function __construct(public Application $app)
    {
    }

    public function register()
    {

    }

    public function boot()
    {

    }

    public function loadRoutesFrom($path, $type = 'api'): void
    {
        $this->app->addRouteFile($path, $type);
    }

    public function loadMigrationsFrom($path): void
    {
        $this->app->addMigrationFolder($path);
    }
}
