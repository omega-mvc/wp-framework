<?php

namespace Omega\Facades;

defined( 'ABSPATH' ) || exit;

/**
 * @method static \Omega\View\ViewServiceProvider make(string $view, array $data = [])
 *
 * @see \Omega\View\ViewServiceProvider
 */
class View extends Facade {
	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() {
		return 'view';
	}
}