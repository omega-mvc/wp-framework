<?php

namespace Omega\Facade;

use Omega\Application\ApplicationInstance;
use Omega\Facade\Exception\FacadeObjectNotSetException;

defined( 'ABSPATH' ) || exit;

abstract class AbstractFacade implements FacadeInterface {
	protected static $resolvedInstance = [];

	public static function __callStatic( $method, $args ) {
		$instance = static::getFacadeRoot();

		if ( ! $instance ) {
			throw new \RuntimeException( 'A facade root has not been set.' );
		}

		return $instance->$method( ...$args );
	}

	public static function getFacadeRoot() {
		return static::resolveFacadeInstance( static::getFacadeAccessor() );
	}

	public static function getFacadeAccessor() {
		throw new FacadeObjectNotSetException( 'Facade does not define a facade accessor.' );
	}

	protected static function resolveFacadeInstance( $name ) {
		if ( isset( static::$resolvedInstance[ $name ] ) ) {
			return static::$resolvedInstance[ $name ];
		}

		return static::$resolvedInstance[ $name ] = ApplicationInstance::app( $name );
	}

	public static function clearResolvedInstance( $name ) {
		unset( static::$resolvedInstance[ $name ] );
	}

	public static function clearResolvedInstances() {
		static::$resolvedInstance = [];
	}
}