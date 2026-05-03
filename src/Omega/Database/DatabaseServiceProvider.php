<?php

declare(strict_types=1);

namespace Omega\Database;

use Omega\Container\ServiceProvider;
use Omega\Database\Migrations\Migrator;

use function add_filter;
use function str_replace;

class DatabaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $app = $this->app;
        $app->singleton('database', function () use ($app) {
            return new Database($app);
        });

        $this->app->singleton('migrator', function () {
            return new Migrator($this->app);
        });
    }

    public function boot(): void
    {
        add_filter('query', [$this, 'nulledQueryReplace']);
    }

    public function nulledQueryReplace($query): array|string
    {
        return str_replace(["IS '!#####NULL#####!'", "IS NOT '!#####NULL#####!'"], ['IS NULL', 'IS NOT NULL'], $query);
    }
}