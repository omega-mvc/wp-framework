<?php

/**
 * Part of Omega - Routing Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   1.0.0
 */

declare(strict_types=1);

namespace Omega\Routing;

use Omega\Container\ServiceProvider;

use function add_action;
use function file_exists;

/**
 * Registers and bootstraps application routing for both REST API and admin areas.
 *
 * This service provider integrates the framework routing layer with WordPress lifecycle hooks,
 * automatically loading route definition files during the appropriate execution phases.
 *
 * It binds the RouterBuilder into the service container as a singleton and ensures that:
 * - API routes are loaded during `rest_api_init`
 * - Admin routes are loaded during `admin_menu`
 *
 * Route definitions are loaded from both the default route files and any additional
 * files registered by the application container.
 *
 * This provider acts as the bridge between the Omega routing system and WordPress execution flow.
 *
 * @category  Omega
 * @package   Routing
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   1.0.0
 */
class RouterServiceProvider extends ServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register(): void
    {
        $this->app->singleton('router', function ($app) {
            return new RouterBuilder($app);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot(): void
    {
        add_action('rest_api_init', [$this, 'registerRestRoutes']);
        add_action('admin_menu', [$this, 'registerAdminRoutes'], 99);
    }

    /**
     * Load and register REST API route definition files.
     *
     * This method loads the default API routes file along with any additional
     * route files registered in the application configuration.
     *
     * Only existing files are included to prevent runtime errors.
     *
     * @return void
     */
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

    /**
     * Load and register admin route definition files.
     *
     * This method loads the default admin routes file along with any additional
     * route files registered in the application configuration.
     *
     * Only existing files are included to ensure safe inclusion.
     *
     * @return void
     */
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
