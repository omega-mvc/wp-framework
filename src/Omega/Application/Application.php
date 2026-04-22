<?php

namespace Omega\Application;

use Omega\Admin\AdminServiceProvider;
use Omega\Config\Config;
use Omega\Config\ConfigServiceProvider;
use Omega\Config\SettingsRepository;
use Omega\Container\Container;
use Omega\Database\DatabaseServiceProvider;
use Omega\Routing\RouterServiceProvider;
use Omega\View\ViewServiceProvider;

defined( 'ABSPATH' ) || exit;

class Application extends Container {

    protected static $instance;

    protected $basePath;

	protected $id;

	protected $snake_id;

	protected $serviceProviders = [];

	protected $routeFiles = [];

	protected $migrationFolders = [];

	protected $pluginRoot;

	public function __construct( string $basePath, string $id ) {
        $this->basePath = rtrim($basePath, '/');
		$this->id = $id;
		/**$this->snake_id = Str::toSnake( $id );

		if ( isset( $config['base_path'] ) ) {
			$this->setBasePath( $config['base_path'] );
		}

		if ( isset( $config['plugin_root'] ) ) {
			$this->pluginRoot = rtrim( $config['plugin_root'], '/' );
		}*/

        static::$instance = $this;

        $this->instance('app', $this);
        $this->instance(Container::class, $this);


		$this->registerBaseBindings();
		$this->registerBaseServiceProviders();
		$this->registerServiceProviders();
		$this->registerCoreContainerAliases();
	}

    public static function getInstance() {
        return static::$instance;
    }

	public function getId() {
		return $this->id;
	}

	/**
	 * Get id with underscore, ex: my-app-id => my_app_id
	 *
	 * @return array|string
	 */
	public function getIdAsUnderscore() {
		return str_replace( '-', '_', $this->id );
	}

	public function getBasePath() {
		return $this->basePath;
	}

	public function pluginRoot() {
		return $this->pluginRoot;
	}

	public function getPluginFile() {
		return "{$this->pluginRoot()}/{$this->getId()}.php";
	}

	public function booted( \Closure $callback ) {
		add_action( "{$this->snake_id}_app_booted", $callback );
	}

	protected function setBasePath( $basePath ) {
		$this->basePath = rtrim( $basePath, '/' );
	}

	protected function registerBaseBindings() {

	}

	protected function registerBaseServiceProviders() {
		$this->register( new ConfigServiceProvider( $this ) );
		$this->register( new RouterServiceProvider( $this ) );
		$this->register( new DatabaseServiceProvider( $this ) );
		$this->register( new ViewServiceProvider( $this ) );
		$this->register( new AdminServiceProvider( $this ) );
	}

	protected function registerServiceProviders() {
		$providersFile = $this->getBasePath() . '/config/providers.php';
		if ( file_exists( $providersFile ) ) {
			$providers = include $providersFile;
			if ( is_array( $providers ) ) {
				foreach ( $providers as $provider ) {
					$this->register( $provider );
				}
			}
		}
	}

	protected function registerCoreContainerAliases() {

	}

	public function register( $provider ) {
		$class = is_string( $provider ) ? $provider : get_class( $provider );
		if ( isset( $this->serviceProviders[ $class ] ) ) {
			return $this->serviceProviders[ $class ];
		}

		if ( is_string( $provider ) ) {
			$provider = new $provider( $this );
		}

		$this->serviceProviders[ $class ] = $provider;
		if ( method_exists( $provider, 'register' ) ) {
			$provider->register();
		}
		return $provider;
	}

	public function bootstrap() {
		foreach ( $this->serviceProviders as $provider ) {
			if ( method_exists( $provider, 'boot' ) ) {
				$provider->boot();
			}
		}

	}

	public function addRouteFile( $path, $type = 'api' ) {
		$this->routeFiles[] = [
			'type' => $type,
			'path' => $path
		];
	}

	public function addMigrationFolder( $path ) {
		$this->migrationFolders[] = $path;
	}

	public function getRestRouteFiles() {
		return array_map(
			fn( $route ) => $route['path'],
			array_filter( $this->routeFiles, fn( $route ) => $route['type'] === 'api' )
		);
	}

	public function getAdminRouteFiles() {
		return array_map(
			fn( $route ) => $route['path'],
			array_filter( $this->routeFiles, fn( $route ) => $route['type'] === 'admin' )
		);
	}

	public function getMigrationFolders() {
		return $this->migrationFolders;
	}

	public function version() {
		$plugin_file = $this->getPluginFile();

		$data = get_file_data(
			$plugin_file,
			[ 'Version' => 'Version' ]
		);

		return $data['Version'] ?? '1.0.0';
	}

	/**
	 * Config Service
	 *
	 * @return Config
	 */
	public function config() {
		return $this->make( 'config' );
	}

	public function getTranslations() {
		$base = $this->getBasePath();
		$path = "{$base}/resources/lang/translations.php";
		return include $path;
	}


	/**
	 * Settings Service
	 *
	 * @return SettingsRepository
	 */
	public function settings() {
		return $this->make( 'settings' );
	}
}