<?php

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

use function array_filter;
use function array_map;
use function file_exists;
use function get_class;
use function get_file_data;
use function is_array;
use function is_string;
use function method_exists;
use function rtrim;

class Application extends Container
{
    protected static Application $instance;

    protected string $basePath;

    protected string $id;

    protected array $serviceProviders = [];

    protected array $routeFiles = [];

    protected array $migrationFolders = [];

    protected string $pluginRoot;

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

    public static function getInstance(): Application
    {
        return static::$instance;
    }

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get id with underscore, ex: my-app-id => my_app_id
     *
     * @return array|string
     */
    public function getIdAsUnderscore(): array|string
    {
        return Str::toSnake($this->id);
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    public function pluginRoot(): string
    {
        return $this->pluginRoot;
    }

    public function getPluginFile(): string
    {
        return "{$this->pluginRoot()}/{$this->getId()}.php";
    }

    protected function setBasePath($basePath): void
    {
        $this->basePath = rtrim($basePath, '/');
    }

    protected function registerBaseBindings()
    {

    }

    protected function registerBaseServiceProviders(): void
    {
        $this->register(new ConfigServiceProvider($this));
        $this->register(new RouterServiceProvider($this));
        $this->register(new DatabaseServiceProvider($this));
        $this->register(new ViewServiceProvider($this));
        $this->register(new AdminServiceProvider($this));
    }

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

    protected function registerCoreContainerAliases()
    {

    }

    public function register($provider)
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

    public function bootstrap(): void
    {
        foreach ($this->serviceProviders as $provider) {
            if (method_exists($provider, 'boot')) {
                $provider->boot();
            }
        }

    }

    public function addRouteFile($path, $type = 'api'): void
    {
        $this->routeFiles[] = [
            'type' => $type,
            'path' => $path
        ];
    }

    public function addMigrationFolder($path): void
    {
        $this->migrationFolders[] = $path;
    }

    public function getRestRouteFiles(): array
    {
        return array_map(
            fn($route) => $route['path'],
            array_filter($this->routeFiles, fn($route) => $route['type'] === 'api')
        );
    }

    public function getAdminRouteFiles(): array
    {
        return array_map(
            fn($route) => $route['path'],
            array_filter($this->routeFiles, fn($route) => $route['type'] === 'admin')
        );
    }

    public function getMigrationFolders(): array
    {
        return $this->migrationFolders;
    }

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
     * Config Service
     *
     * @return ConfigRepository
     */
    public function config(): ConfigRepository
    {
        return $this->make('config');
    }

    public function getTranslations()
    {
        $base = $this->getBasePath();
        $path = "$base/resources/lang/translations.php";

        return include $path;
    }


    /**
     * Settings Service
     *
     * @return SettingsRepository
     */
    public function settings(): SettingsRepository
    {
        return $this->make('settings');
    }
}
