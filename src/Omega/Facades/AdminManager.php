<?php

namespace Omega\Facades;

defined( 'ABSPATH' ) || exit;

/**
 * @method static \Omega\Admin\Page addMenu(array $args)
 * @method static \Omega\Admin\Page addSubMenu(array $args)
 * 
 * @see \Omega\Admin\AdminManager
 */
class AdminManager extends Facade {
	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() {
		return 'admin.manager';
	}
}