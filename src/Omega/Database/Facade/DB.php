<?php

namespace Omega\Database\Facade;

use Omega\Facade\AbstractFacade;

defined( 'ABSPATH' ) || exit;

/**
 * @method static \Omega\Database\Migrations\Migrator migrator()
 * @method static \Omega\Database\Eloquent\QueryBuilder table( string $table )
 *
 * @see \Omega\Database\Database
 */
class DB extends AbstractFacade {

	public static function getFacadeAccessor() {
		return 'database';
	}
}