<?php

/**
 * Part of Omega - Application Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   1.0.0
 */

declare(strict_types=1);

namespace Omega\Application;

use Omega\Admin\AdminServiceProvider;
use Omega\Config\ConfigRepository;
use Omega\Config\ConfigServiceProvider;
use Omega\Config\SettingsRepository;
use Omega\Container\Container;
use Omega\Database\DatabaseServiceProvider;
use Omega\Routing\RouterServiceProvider;
use Omega\Str\Str;
use Omega\View\ViewServiceProvider;
use ReflectionException;

use function array_filter;
use function array_map;
use function file_exists;
use function get_class;
use function get_file_data;
use function is_array;
use function is_string;
use function method_exists;
use function rtrim;

/**
 * Core application container responsible for bootstrapping and managing services.
 *
 * Handles service provider registration, routing, migrations, and integration
 * with the WordPress plugin lifecycle.
 *
 * @category  Omega
 * @package   Application
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   1.0.0
 */
class Application extends Container
{
    /** @var Application Singleton instance of the application. */
    protected static Application $instance;

    /** @var string Base path of the application. */
    protected string $basePath;

    /** @var string Unique application identifier. */
    protected string $id;

    /** @var array Registered service provider instances. */
    protected array $serviceProviders = [];

    /** @var array Route file definitions grouped by type. */
    protected array $routeFiles = [];

    /** @var array Registered migration folder paths. */
    protected array $migrationFolders = [];

    /** @var string Root directory of the plugin. */
    protected string $pluginRoot;

    /**
     * Create a new application instance and initialize core services.
     *
     * @param array  $config Configuration array (base_path, plugin_root, etc.)
     * @param string $id     Unique application identifier
     * @return void
     */
    public function __construct(array $config, string $id)
    {
        $this->id = $id;

        if (isset($config['base_path'])) {
            $this->setBasePath($config['base_path']);
        }

        if (isset($config['plugin_root'])) {
            $this->pluginRoot = rtrim($config['plugin_root'], '/');
        }

        static::$instance = $this;

        $this->instance('app', $this);
        $this->instance(Container::class, $this);

        $this->registerBaseBindings();
        $this->registerBaseServiceProviders();
        $this->registerServiceProviders();
        $this->registerCoreContainerAliases();
    }

    /**
     * Get the current application instance.
     *
     * @return Application
     */
    public static function getInstance(): Application
    {
        return static::$instance;
    }

    /**
     * Get the unique application identifier.
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get the application id in snake_case format.
     *
     * @return string|array
     */
    public function getIdAsUnderscore(): array|string
    {
        return Str::toSnake($this->id);
    }

    /**
     * Get the base path of the application.
     *
     * @return string
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * Get the root directory of the plugin.
     *
     * @return string
     */
    public function pluginRoot(): string
    {
        return $this->pluginRoot;
    }

    /**
     * Get the main plugin file path.
     *
     * @return string
     */
    public function getPluginFile(): string
    {
        return "{$this->pluginRoot()}/{$this->getId()}.php";
    }

    /**
     * Set the base path for the application.
     *
     * @param string $basePath Base directory path
     * @return void
     */
    protected function setBasePath(string $basePath): void
    {
        $this->basePath = rtrim($basePath, '/');
    }

    /**
     * Register core container bindings required by the application.
     *
     * @return void
     */
    protected function registerBaseBindings()
    {
    }

    /**
     * Register the default framework service providers.
     *
     * @return void
     */
    protected function registerBaseServiceProviders(): void
    {
        $this->register(new ConfigServiceProvider($this));
        $this->register(new RouterServiceProvider($this));
        $this->register(new DatabaseServiceProvider($this));
        $this->register(new ViewServiceProvider($this));
        $this->register(new AdminServiceProvider($this));
    }

    /**
     * Register user-defined service providers from configuration file.
     *
     * @return void
     */
    protected function registerServiceProviders(): void
    {
        $providersFile = $this->getBasePath() . '/config/providers.php';
        if (file_exists($providersFile)) {
            $providers = include $providersFile;
            if (is_array($providers)) {
                foreach ($providers as $provider) {
                    $this->register($provider);
                }
            }
        }
    }

    /**
     * Register core container aliases for internal services.
     *
     * @return void
     */
    protected function registerCoreContainerAliases()
    {
    }

    /**
     * Register a service provider within the application container.
     *
     * @param object|string $provider Service provider class name or instance
     * @return object|string Registered service provider instance
     */
    public function register(object|string $provider): object|string
    {
        $class = is_string($provider) ? $provider : get_class($provider);

        if (isset($this->serviceProviders[$class])) {
            return $this->serviceProviders[$class];
        }

        if (is_string($provider)) {
            $provider = new $provider($this);
        }

        $this->serviceProviders[$class] = $provider;

        if (method_exists($provider, 'register')) {
            $provider->register();
        }

        return $provider;
    }

    /**
     * Bootstrap all registered service providers.
     *
     * Calls the "boot" method on each provider if available.
     *
     * @return void
     */
    public function bootstrap(): void
    {
        foreach ($this->serviceProviders as $provider) {
            if (method_exists($provider, 'boot')) {
                $provider->boot();
            }
        }
    }

    /**
     * Add a route file to the application.
     *
     * @param string $path Path to the route file
     * @param string $type Route type (e.g. "api" or "admin")
     * @return void
     */
    public function addRouteFile(string $path, string $type = 'api'): void
    {
        $this->routeFiles[] = [
            'type' => $type,
            'path' => $path
        ];
    }

    /**
     * Register a migration folder path.
     *
     * @param string $path Directory containing migration files
     * @return void
     */
    public function addMigrationFolder(string $path): void
    {
        $this->migrationFolders[] = $path;
    }

    /**
     * Get all registered API route file paths.
     *
     * @return array List of API route file paths
     */
    public function getRestRouteFiles(): array
    {
        return array_map(
            fn($route) => $route['path'],
            array_filter($this->routeFiles, fn($route) => $route['type'] === 'api')
        );
    }

    /**
     * Get all registered admin route file paths.
     *
     * @return array List of admin route file paths
     */
    public function getAdminRouteFiles(): array
    {
        return array_map(
            fn($route) => $route['path'],
            array_filter($this->routeFiles, fn($route) => $route['type'] === 'admin')
        );
    }

    /**
     * Get all registered migration folder paths.
     *
     * @return array List of migration directories
     */
    public function getMigrationFolders(): array
    {
        return $this->migrationFolders;
    }

    /**
     * Retrieve the plugin version from its main file header.
     *
     * @return string Plugin version or default if not found
     */
    public function version(): string
    {
        $plugin_file = $this->getPluginFile();

        $data = get_file_data(
            $plugin_file,
            ['Version' => 'Version']
        );

        return $data['Version'] ?? '1.0.0';
    }

    /**
     * Get the configuration repository instance.
     *
     * @return ConfigRepository Configuration service instance
     * @throws ReflectionException If the service cannot be resolved
     */
    public function config(): ConfigRepository
    {
        return $this->make('config');
    }

    /**
     * Load translation definitions from the application resources.
     *
     * @return array Translation key-value pairs
     */
    public function getTranslations(): array
    {
        $base = $this->getBasePath();
        $path = "$base/resources/lang/translations.php";

        return include $path;
    }

    /**
     * Get the settings repository instance.
     *
     * @return SettingsRepository Settings service instance
     * @throws ReflectionException If the service cannot be resolved
     */
    public function settings(): SettingsRepository
    {
        return $this->make('settings');
    }
}
