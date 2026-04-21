<?php

namespace Omega\Facades;

defined( 'ABSPATH' ) || exit;

/**
 * @method static \Omega\Routing\Router get(string $uri, array|string|callable|null $action = null)
 * @method static \Omega\Routing\Router post(string $uri, array|string|callable|null $action = null)
 * @method static \Omega\Routing\Router put(string $uri, array|string|callable|null $action = null)
 * @method static \Omega\Routing\Router delete(string $uri, array|string|callable|null $action = null)
 * @method static \Omega\Routing\Router patch(string $uri, array|string|callable|null $action = null)
 * @method static \Omega\Routing\Router prefix(string $prefix)
 * @method static \Omega\Routing\Router guards(array $guards)
 * @method static \Omega\Routing\Router page(string $id, $options = [])
 *
 * @see \Omega\Routing\Router
 */
class Route extends Facade {
	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() {
		return 'router';
	}
}