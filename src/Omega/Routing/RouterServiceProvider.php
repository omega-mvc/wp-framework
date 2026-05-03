<?php

declare(strict_types=1);

namespace Omega\Routing;

use Omega\Container\ServiceProvider;

use function add_action;
use function file_exists;

class RouterServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('router', function ($app) {
            return new RouterBuilder($app);
        });
    }

    public function boot(): void
    {
        add_action('rest_api_init', [$this, 'registerRestRoutes']);
        add_action('admin_menu', [$this, 'registerAdminRoutes'], 99);
    }

    public function registerRestRoutes(): void
    {
        $apiRoutesPath = $this->app->getBasePath() . '/routes/api.php';

        $routes = [
            $apiRoutesPath,
            ...$this->app->getRestRouteFiles()
        ];

        foreach ($routes as $routeFile) {
            if (file_exists($routeFile)) {
                require_once $routeFile;
            }
        }
    }

    public function registerAdminRoutes(): void
    {
        $adminRoutesPath = $this->app->getBasePath() . '/routes/admin.php';

        $routes = [
            $adminRoutesPath,
            ...$this->app->getAdminRouteFiles()
        ];

        foreach ($routes as $routeFile) {
            if (file_exists($routeFile)) {
                require_once $routeFile;
            }
        }
    }
}
