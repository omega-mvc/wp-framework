<?php

namespace Omega\View\Facade;

use Omega\Facade\AbstractFacade;

defined( 'ABSPATH' ) || exit;

/**
 * @method static \Omega\View\ViewServiceProvider make(string $view, array $data = [])
 *
 * @see \Omega\View\ViewServiceProvider
 */
class View extends AbstractFacade {
	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	public static function getFacadeAccessor() {
		return 'view';
	}
}