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
use Omega\Application\Exception\MissingParameterException;
use Omega\Config\ConfigRepository;
use Omega\Config\ConfigServiceProvider;
use Omega\Container\Container;
use Omega\Database\DatabaseServiceProvider;
use Omega\Routing\RouterServiceProvider;
use Omega\Settings\SettingsRepository;
use Omega\Settings\SettingsServiceProvider;
use Omega\Str\Str;
use Omega\View\ViewServiceProvider;
use ReflectionException;

use function array_filter;
use function array_map;
use function file_exists;
use function get_class;
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
abstract class AbstractApplication extends Container implements ApplicationInterface
{
	#region Property
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
    protected string $appRoot = '';
    #endregion

	#region Public Method's
    /**
     * Create and initialize a new application instance.
     *
     * This constructor is responsible for setting up the core state of the application,
     * validating the required initialization parameters, and triggering the registration
     * of all fundamental framework components.
     *
     * It performs the following steps in order:
     * - Validates the provided application identifier and base path
     * - Assigns the application ID
     * - Initializes the base path of the application
     * - Defines the application root directory
     * - Registers core container bindings
     * - Registers base framework service providers
     * - Registers user-defined service providers
     * - Registers core container aliases
     *
     * The application instance is fully bootstrapped at the end of this process,
     * meaning that all core services are available for resolution and use.
     *
     * @param string $id Unique identifier of the application instance.
     *                   This value is used to distinguish between multiple
     *                   applications within the same runtime environment.
     * @param string $basePath Absolute path to the root directory of the application.
     *                         This path is used as the base for configuration,
     *                         service discovery, and file resolution.
     * @return void
     */
    public function __construct( string $id, string $basePath)
    {
        $this->isValidData($id, $basePath);

        $this->id = $id;

        $this->setBasePath($basePath);
        $this->appRoot = rtrim($basePath, '/');

        $this->registerBaseBindings();
        $this->registerBaseServiceProviders();
        $this->registerServiceProviders();
        $this->registerCoreContainerAliases();
    }

	/**
	 * {@inheritdoc}
	 */
    public function getId(): string
    {
        return $this->id;
    }

	/**
	 * {@inheritdoc}
	 */
    public function getIdAsUnderscore(): array|string
    {
        return Str::toSnake($this->id);
    }

	/**
	 * {@inheritdoc}
	 */
    public function getBasePath(): string
    {
        return $this->basePath;
    }

	/**
	 * {@inheritdoc}
	 */
    public function getAppRoot(): string
    {
        return $this->appRoot;
    }

	/**
	 * {@inheritdoc}
	 */
	public function getAppFile(): string
	{
		if (function_exists('wp_get_theme')
		    && class_exists('WP_Theme')
		    && wp_get_theme($this->id)->exists()
		) {
			return "{$this->getAppRoot()}/style.css";
		}

		return "{$this->getAppRoot()}/{$this->getId()}.php";
	}

	/**
	 * {@inheritdoc}
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
	 * {@inheritdoc}
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
	 * {@inheritdoc}
	 */
    public function addRouteFile(string $path, string $type = 'api'): void
    {
        $this->routeFiles[] = [
            'type' => $type,
            'path' => $path
        ];
    }

	/**
	 * {@inheritdoc}
	 */
    public function addMigrationFolder(string $path): void
    {
        $this->migrationFolders[] = $path;
    }

	/**
	 * {@inheritdoc}
	 */
    public function getRestRouteFiles(): array
    {
        return array_map(
            fn($route) => $route['path'],
            array_filter($this->routeFiles, fn($route) => $route['type'] === 'api')
        );
    }

	/**
	 * {@inheritdoc}
	 */
    public function getAdminRouteFiles(): array
    {
        return array_map(
            fn($route) => $route['path'],
            array_filter($this->routeFiles, fn($route) => $route['type'] === 'admin')
        );
    }

	/**
	 * {@inheritdoc}
	 */
    public function getMigrationFolders(): array
    {
        return $this->migrationFolders;
    }

	/**
	 * {@inheritdoc}
	 *
	 * @throws ReflectionException If the service cannot be resolved
	 */
    public function config(): ConfigRepository
    {
        return $this->make('config');
    }

    /**
     * {@inheritdoc}
     *
     * @throws ReflectionException If the service cannot be resolved
     */
    public function settings(): SettingsRepository
    {
        return $this->make('settings');
    }
    #endregion

	#region Protected Method's
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
        $this->register(new SettingsServiceProvider($this));
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
	#endregion

    #region Private Method's
    /**
     * Validate the minimum required parameters for application initialization.
     *
     * This method ensures that the essential constructor arguments are provided
     * and meet the basic requirements needed to safely bootstrap the application.
     *
     * It performs validation on:
     * - The application identifier (`$id`)
     * - The base path of the application (`$basePath`)
     *
     * If any of the required parameters are missing or empty, the method will
     * interrupt the initialization process by throwing a MissingParameterException.
     *
     * This validation step guarantees that the application cannot be instantiated
     * in an invalid or incomplete state.
     *
     * @param string $id Unique identifier of the application instance.
     *                   Must be a non-empty string.
     * @param string $basePath Absolute path to the root directory of the application.
     *                         Must be a non-empty string pointing to a valid location.
     * @return void
     * @throws MissingParameterException When either `$id` or `$basePath` is empty.
     */
    private function isValidData(string $id, string $basePath): void
    {
        if (empty($id)) {
            throw new MissingParameterException('The "id" parameter is required.');
        }

        if (empty($basePath)) {
            throw new MissingParameterException('The "basePath" parameter is required.');
        }
    }
    #endregion
}
