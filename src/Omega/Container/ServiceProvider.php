<?php

namespace Omega\Container;

use Omega\Application\Application;

defined( 'ABSPATH' ) || exit;

class ServiceProvider {

	/**
	 * The application instance.
	 *
	 * @var Application
	 */
	public $app;

	public function __construct( Application $app ) {
		$this->app = $app;
	}


	public function register() {

	}

	public function boot() {

	}

	public function loadRoutesFrom( $path, $type = 'api' ) {
		$this->app->addRouteFile( $path, $type );
	}

	public function loadMigrationsFrom( $path ) {
		$this->app->addMigrationFolder( $path );
	}
}