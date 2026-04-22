<?php

namespace Omega\Admin\Facade;

use Omega\Facade\AbstractFacade;

defined( 'ABSPATH' ) || exit;

/**
 * @method static \Omega\Admin\Page addMenu(array $args)
 * @method static \Omega\Admin\Page addSubMenu(array $args)
 * 
 * @see \Omega\Admin\AdminManager
 */
class AdminManager extends AbstractFacade {
	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	public static function getFacadeAccessor() {
		return 'admin.manager';
	}
}